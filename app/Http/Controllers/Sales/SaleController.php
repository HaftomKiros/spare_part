<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PartCategory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SparePart;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(Request $request)
    {
        $query = Sale::with('customer', 'user');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('invoice_number', 'like', "%{$request->search}%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$request->search}%"));
            });
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->date_from) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        $sales = $query->latest()->paginate(20)->withQueryString();

        $totals = Sale::where('status', 'completed')
            ->selectRaw('SUM(total) as grand_total, SUM(paid_amount) as grand_paid, SUM(balance) as grand_balance')
            ->first();

        return view('sales.sales.index', compact('sales', 'totals'));
    }

    public function create()
    {
        $customers    = Customer::active()->orderBy('name')->get();
        $vehicleTypes = VehicleType::active()->with('activeVehicleModels.stock')->get();
        $categories   = PartCategory::active()->with('spareParts.unit')->orderBy('name')->get();
        $invoice      = Sale::generateInvoiceNumber();

        // Pre-encode JSON for JS (avoids PHP 8.5 parse issues with @json + arrow functions)
        $vehicleTypesJson = json_encode($vehicleTypes->map(function ($vt) {
            return [
                'id'     => $vt->id,
                'name'   => $vt->name,
                'models' => $vt->activeVehicleModels->map(function ($m) {
                    return [
                        'id'    => $m->id,
                        'name'  => $m->brand . ' ' . $m->model_name . ($m->model_code ? ' (' . $m->model_code . ')' : ''),
                        'price' => $m->selling_price,
                        'stock' => $m->stock?->current_stock ?? 0,
                    ];
                })->values(),
            ];
        })->values());

        $categoriesJson = json_encode($categories->map(function ($cat) {
            return [
                'id'    => $cat->id,
                'name'  => $cat->name,
                'parts' => $cat->spareParts->map(function ($p) {
                    return [
                        'id'    => $p->id,
                        'name'  => $p->name . ' (' . $p->part_number . ')',
                        'price' => $p->selling_price,
                        'stock' => $p->current_stock,
                        'unit'  => $p->unit->abbreviation,
                    ];
                })->values(),
            ];
        })->values());

        return view('sales.sales.create', compact('customers', 'vehicleTypes', 'categories', 'invoice', 'vehicleTypesJson', 'categoriesJson'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sale_date'      => 'required|date',
            'customer_id'    => 'nullable|exists:customers,id',
            'payment_method' => 'required|in:cash,bank_transfer,cheque,credit',
            'paid_amount'    => 'required|numeric|min:0',
            'subtotal'       => 'required|numeric|min:0',
            'discount'       => 'nullable|numeric|min:0',
            'tax'            => 'nullable|numeric|min:0',
            'total'          => 'required|numeric|min:0',
            'notes'          => 'nullable|string|max:500',
            'items'                  => 'required|array|min:1',
            'items.*.item_type'      => 'required|in:vehicle,spare_part',
            'items.*.item_id'        => 'required|integer',
            'items.*.quantity'       => 'required|integer|min:1',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.discount'       => 'nullable|numeric|min:0',
            'items.*.total'          => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $total      = (float) $request->total;
            $paid       = (float) $request->paid_amount;
            $balance    = max(0, $total - $paid);

            $payStatus = $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

            $sale = Sale::create([
                'invoice_number' => Sale::generateInvoiceNumber(),
                'customer_id'    => $request->customer_id ?: null,
                'user_id'        => auth()->id(),
                'sale_date'      => $request->sale_date,
                'subtotal'       => $request->subtotal,
                'discount'       => $request->discount ?? 0,
                'tax'            => $request->tax ?? 0,
                'total'          => $total,
                'paid_amount'    => $paid,
                'balance'        => $balance,
                'payment_method' => $request->payment_method,
                'payment_status' => $payStatus,
                'status'         => 'completed',
                'notes'          => $request->notes,
            ]);

            foreach ($request->items as $row) {
                SaleItem::create([
                    'sale_id'          => $sale->id,
                    'item_type'        => $row['item_type'],
                    'vehicle_model_id' => $row['item_type'] === 'vehicle'     ? $row['item_id'] : null,
                    'spare_part_id'    => $row['item_type'] === 'spare_part'  ? $row['item_id'] : null,
                    'quantity'         => $row['quantity'],
                    'unit_price'       => $row['unit_price'],
                    'discount'         => $row['discount'] ?? 0,
                    'total'            => $row['total'],
                ]);
            }

            // Deduct stock
            $sale->load('items.vehicleModel', 'items.sparePart');
            $this->stockService->processSaleStock($sale);

            // Update customer balance if on credit
            if ($sale->customer_id && $balance > 0) {
                $sale->customer->increment('balance', $balance);
            }

            DB::commit();
            return redirect()->route('sales.show', $sale)
                ->with('success', "Invoice #{$sale->invoice_number} created successfully.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create sale: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Sale $sale)
    {
        $sale->load('customer', 'user', 'items.vehicleModel.vehicleType', 'items.sparePart.category', 'returns');
        return view('sales.sales.show', compact('sale'));
    }

    public function invoice(Sale $sale)
    {
        $sale->load('customer', 'user', 'items.vehicleModel.vehicleType', 'items.sparePart.unit');
        $company = \App\Models\CompanySetting::getInstance();
        return view('sales.sales.invoice', compact('sale', 'company'));
    }

    public function destroy(Sale $sale)
    {
        if ($sale->status === 'completed') {
            return back()->with('error', 'Cannot delete a completed sale. Cancel it first if needed.');
        }
        $sale->delete();
        return redirect()->route('sales.index')
            ->with('success', 'Sale deleted.');
    }

    /**
     * AJAX: search items for sale form autocomplete
     */
    public function searchItems(Request $request)
    {
        $q    = $request->get('q', '');
        $type = $request->get('type', 'all');

        $results = [];

        if ($type !== 'spare_part') {
            $vehicles = VehicleModel::active()
                ->with('vehicleType', 'stock')
                ->where(fn($q2) => $q2->where('model_name', 'like', "%{$q}%")->orWhere('model_code', 'like', "%{$q}%"))
                ->limit(10)->get();

            foreach ($vehicles as $v) {
                $results[] = [
                    'id'    => $v->id,
                    'type'  => 'vehicle',
                    'name'  => $v->full_name,
                    'price' => $v->selling_price,
                    'stock' => $v->stock?->current_stock ?? 0,
                    'code'  => $v->model_code,
                ];
            }
        }

        if ($type !== 'vehicle') {
            $parts = SparePart::active()
                ->with('unit')
                ->where(fn($q2) => $q2->where('name', 'like', "%{$q}%")->orWhere('part_number', 'like', "%{$q}%"))
                ->limit(10)->get();

            foreach ($parts as $p) {
                $results[] = [
                    'id'    => $p->id,
                    'type'  => 'spare_part',
                    'name'  => $p->name,
                    'price' => $p->selling_price,
                    'stock' => $p->current_stock,
                    'code'  => $p->part_number,
                    'unit'  => $p->unit->abbreviation,
                ];
            }
        }

        return response()->json($results);
    }
}
