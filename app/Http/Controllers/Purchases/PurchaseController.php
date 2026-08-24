<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\PartCategory;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\SparePart;
use App\Models\Supplier;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(Request $request)
    {
        $query = Purchase::with('supplier', 'user');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('purchase_number', 'like', "%{$request->search}%")
                  ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', "%{$request->search}%"));
            });
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->date_from) {
            $query->whereDate('purchase_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('purchase_date', '<=', $request->date_to);
        }

        $purchases = $query->latest()->paginate(20)->withQueryString();

        $totals = Purchase::selectRaw('
            SUM(total) as grand_total,
            SUM(paid_amount) as grand_paid,
            SUM(balance) as grand_balance
        ')->first();

        return view('purchases.purchases.index', compact('purchases', 'totals'));
    }

    public function create()
    {
        $suppliers    = Supplier::active()->orderBy('name')->get();
        $vehicleTypes = VehicleType::active()->with('activeVehicleModels.stock')->get();
        $categories   = PartCategory::active()->with('spareParts.unit')->orderBy('name')->get();
        $number       = Purchase::generateNumber();

        // Pre-encode JSON for JS (avoids PHP 8.5 parse issues with @json + arrow functions)
        $vehicleTypesJson = json_encode($vehicleTypes->map(function ($vt) {
            return [
                'id'     => $vt->id,
                'name'   => $vt->name,
                'models' => $vt->activeVehicleModels->map(function ($m) {
                    return [
                        'id'    => $m->id,
                        'name'  => $m->brand . ' ' . $m->model_name . ($m->model_code ? ' (' . $m->model_code . ')' : ''),
                        'price' => $m->buying_price,
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
                        'price' => $p->buying_price,
                        'stock' => $p->current_stock,
                        'unit'  => $p->unit->abbreviation,
                    ];
                })->values(),
            ];
        })->values());

        return view('purchases.purchases.create', compact('suppliers', 'vehicleTypes', 'categories', 'number', 'vehicleTypesJson', 'categoriesJson'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id'    => 'required|exists:suppliers,id',
            'purchase_date'  => 'required|date',
            'due_date'       => 'nullable|date|after_or_equal:purchase_date',
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
            $total   = (float) $request->total;
            $paid    = (float) $request->paid_amount;
            $balance = max(0, $total - $paid);
            $payStatus = $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

            $purchase = Purchase::create([
                'purchase_number' => Purchase::generateNumber(),
                'supplier_id'     => $request->supplier_id,
                'user_id'         => auth()->id(),
                'purchase_date'   => $request->purchase_date,
                'due_date'        => $request->due_date ?: null,
                'subtotal'        => $request->subtotal,
                'discount'        => $request->discount ?? 0,
                'tax'             => $request->tax ?? 0,
                'total'           => $total,
                'paid_amount'     => $paid,
                'balance'         => $balance,
                'payment_status'  => $payStatus,
                'status'          => 'received',   // Mark as received immediately
                'notes'           => $request->notes,
            ]);

            foreach ($request->items as $row) {
                PurchaseItem::create([
                    'purchase_id'      => $purchase->id,
                    'item_type'        => $row['item_type'],
                    'vehicle_model_id' => $row['item_type'] === 'vehicle'    ? $row['item_id'] : null,
                    'spare_part_id'    => $row['item_type'] === 'spare_part' ? $row['item_id'] : null,
                    'quantity'         => $row['quantity'],
                    'unit_price'       => $row['unit_price'],
                    'discount'         => $row['discount'] ?? 0,
                    'total'            => $row['total'],
                ]);
            }

            // Update stock
            $purchase->load('items.vehicleModel', 'items.sparePart');
            $this->stockService->processPurchaseStock($purchase);

            // Update supplier balance if unpaid
            if ($balance > 0) {
                $purchase->supplier->increment('balance', $balance);
            }

            DB::commit();
            return redirect()->route('purchases.show', $purchase)
                ->with('success', "Purchase #{$purchase->purchase_number} created and stock updated.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to save purchase: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('supplier', 'user', 'items.vehicleModel.vehicleType', 'items.sparePart.category');
        return view('purchases.purchases.show', compact('purchase'));
    }

    public function receive(Request $request, Purchase $purchase)
    {
        if ($purchase->status === 'received') {
            return back()->with('error', 'This purchase has already been received.');
        }

        DB::beginTransaction();
        try {
            $purchase->update(['status' => 'received']);
            $purchase->load('items.vehicleModel', 'items.sparePart');
            $this->stockService->processPurchaseStock($purchase);

            DB::commit();
            return back()->with('success', "Purchase #{$purchase->purchase_number} marked as received. Stock updated.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroy(Purchase $purchase)
    {
        if ($purchase->status === 'received') {
            return back()->with('error', 'Cannot delete a received purchase. The stock has already been updated.');
        }
        $purchase->delete();
        return redirect()->route('purchases.index')
            ->with('success', 'Purchase order deleted.');
    }

    /**
     * AJAX: search items for purchase form
     */
    public function searchItems(Request $request)
    {
        $q    = $request->get('q', '');
        $type = $request->get('type', 'all');
        $results = [];

        if ($type !== 'spare_part') {
            $vehicles = VehicleModel::active()->with('vehicleType')
                ->where(fn($q2) => $q2->where('model_name', 'like', "%{$q}%")->orWhere('model_code', 'like', "%{$q}%"))
                ->limit(10)->get();
            foreach ($vehicles as $v) {
                $results[] = [
                    'id'    => $v->id,
                    'type'  => 'vehicle',
                    'name'  => $v->full_name,
                    'price' => $v->buying_price,
                    'code'  => $v->model_code,
                ];
            }
        }

        if ($type !== 'vehicle') {
            $parts = SparePart::active()->with('unit')
                ->where(fn($q2) => $q2->where('name', 'like', "%{$q}%")->orWhere('part_number', 'like', "%{$q}%"))
                ->limit(10)->get();
            foreach ($parts as $p) {
                $results[] = [
                    'id'    => $p->id,
                    'type'  => 'spare_part',
                    'name'  => $p->name,
                    'price' => $p->buying_price,
                    'code'  => $p->part_number,
                    'unit'  => $p->unit->abbreviation,
                ];
            }
        }

        return response()->json($results);
    }
}
