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
        $user          = auth()->user();
        $accessibleIds = $user->accessibleWarehouseIds();

        $query = Sale::with('customer', 'user')
            ->whereIn('warehouse_id', $accessibleIds);

        // Scope to current user's own sales within their warehouses
        if (! $user->seesAllUsers()) {
            $query->where('user_id', $user->id);
        }

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

        $totalsQuery = Sale::where('status', 'completed')
            ->whereIn('warehouse_id', $accessibleIds);
        if (! $user->seesAllUsers()) {
            $totalsQuery->where('user_id', $user->id);
        }
        $totals = $totalsQuery
            ->selectRaw('SUM(total) as grand_total, SUM(paid_amount) as grand_paid, SUM(balance) as grand_balance')
            ->first();

        return view('sales.sales.index', compact('sales', 'totals'));
    }

    public function create()
    {
        $customers        = Customer::active()->orderBy('name')->get();
        $warehouses       = auth()->user()->accessibleWarehouses()->get();
        $defaultWarehouse = $warehouses->firstWhere('is_default', true) ?? $warehouses->first();
        $invoice          = Sale::generateInvoiceNumber();

        return view('sales.sales.create', compact(
            'customers', 'warehouses', 'defaultWarehouse', 'invoice'
        ));
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
            'items.*.item_type'        => 'required|in:vehicle,spare_part',
            'items.*.item_id'          => 'required|integer',
            'items.*.purchase_item_id' => 'nullable|integer|exists:purchase_items,id',
            'items.*.quantity'         => 'required|integer|min:1',
            'items.*.unit_price'       => 'required|numeric|min:0',
            'items.*.discount'         => 'nullable|numeric|min:0',
            'items.*.total'            => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $total      = (float) $request->total;
            $paid       = (float) $request->paid_amount;
            $balance    = max(0, $total - $paid);

            $payStatus = $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

            // Server-side stock check per warehouse
            $warehouseId = $request->warehouse_id ?: \App\Models\Warehouse::getDefault()?->id;
            foreach ($request->items as $row) {
                $qty = (int) $row['quantity'];
                if ($row['item_type'] === 'spare_part') {
                    $available = DB::table('warehouse_spare_part_stock')
                        ->where('warehouse_id', $warehouseId)
                        ->where('spare_part_id', $row['item_id'])
                        ->value('current_stock') ?? 0;
                } else {
                    $available = DB::table('warehouse_vehicle_stock')
                        ->where('warehouse_id', $warehouseId)
                        ->where('vehicle_model_id', $row['item_id'])
                        ->value('current_stock') ?? 0;
                }
                if ($qty > $available) {
                    $name = $row['item_type'] === 'spare_part'
                        ? \App\Models\SparePart::find($row['item_id'])?->name
                        : \App\Models\VehicleModel::find($row['item_id'])?->full_name;
                    DB::rollBack();
                    return back()
                        ->with('error', "Insufficient stock for '{$name}'. Available: {$available}, Requested: {$qty}.")
                        ->withInput();
                }

                // If a specific purchase batch was selected, validate it has enough remaining stock
                if (!empty($row['purchase_item_id'])) {
                    $pi = DB::table('purchase_items')->where('id', $row['purchase_item_id'])->first();
                    if (!$pi) {
                        return back()->with('error', 'Selected purchase batch not found.')->withInput();
                    }
                    $remaining = $pi->quantity - $pi->total_sold;
                    if ($qty > $remaining) {
                        $name = $row['item_type'] === 'spare_part'
                            ? \App\Models\SparePart::find($row['item_id'])?->name
                            : \App\Models\VehicleModel::find($row['item_id'])?->full_name;
                        return back()
                            ->with('error', "Quantity ({$qty}) exceeds remaining stock in selected purchase batch ({$remaining}) for '{$name}'.")
                            ->withInput();
                    }
                }
            }

            $sale = Sale::create([
                'invoice_number' => Sale::generateInvoiceNumber(),
                'customer_id'    => $request->customer_id ?: null,
                'user_id'        => auth()->id(),
                'warehouse_id'   => $request->warehouse_id ?: \App\Models\Warehouse::getDefault()?->id,
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
                    'purchase_item_id' => !empty($row['purchase_item_id']) ? (int) $row['purchase_item_id'] : null,
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
        $sale->load('customer', 'user', 'items.vehicleModel.vehicleType', 'items.sparePart.category', 'items.purchaseItem.purchase', 'returns');
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
     * AJAX: get items that exist in a specific warehouse with their warehouse stock
     */
    public function warehouseItems(Request $request)
    {
        $warehouseId = (int) $request->warehouse_id;

        if (!$warehouseId) {
            return response()->json(['vehicles' => [], 'categories' => []]);
        }

        // Enforce: user can only query warehouses they have access to
        if (! in_array($warehouseId, auth()->user()->accessibleWarehouseIds())) {
            return response()->json(['vehicles' => [], 'categories' => []]);
        }

        // Spare parts in this warehouse with current_stock > 0
        $parts = DB::table('warehouse_spare_part_stock as ws')
            ->join('spare_parts as sp', 'ws.spare_part_id', '=', 'sp.id')
            ->join('part_categories as pc', 'sp.part_category_id', '=', 'pc.id')
            ->join('units as u', 'sp.unit_id', '=', 'u.id')
            ->where('ws.warehouse_id', $warehouseId)
            ->where('ws.current_stock', '>', 0)
            ->where('sp.status', 'active')
            ->select(
                'sp.id', 'sp.name', 'sp.part_number', 'sp.buying_price',
                'sp.selling_price_min', 'sp.selling_price_max',
                'ws.current_stock', 'ws.reorder_level',
                'pc.id as category_id', 'pc.name as category_name',
                'u.abbreviation as unit'
            )
            ->orderBy('pc.name')
            ->orderBy('sp.name')
            ->get();

        // Which spare_part_ids have at least one purchase batch with remaining stock
        $partIdsWithBatches = DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->where('p.warehouse_id', $warehouseId)
            ->where('p.status', 'received')
            ->where('pi.item_type', 'spare_part')
            ->whereRaw('pi.quantity > pi.total_sold')
            ->pluck('pi.spare_part_id')
            ->unique()
            ->toArray();

        // Group parts by category — only include parts that have batches
        $categories = [];
        foreach ($parts as $p) {
            $hasBatches = in_array($p->id, $partIdsWithBatches);
            // Only include parts that have unsold purchase batches
            if (!$hasBatches) continue;

            $catId = $p->category_id;
            if (!isset($categories[$catId])) {
                $categories[$catId] = [
                    'id'    => $catId,
                    'name'  => $p->category_name,
                    'parts' => [],
                ];
            }
            $categories[$catId]['parts'][] = [
                'id'        => $p->id,
                'name'      => $p->name . ' (' . $p->part_number . ')',
                'price'     => $p->selling_price_max,
                'price_min' => $p->selling_price_min,
                'price_max' => $p->selling_price_max,
                'buy_price' => $p->buying_price,
                'stock'     => $p->current_stock,
                'reorder'   => $p->reorder_level,
                'unit'      => $p->unit,
            ];
        }

        // Vehicle models in this warehouse with current_stock > 0
        $vehicles = DB::table('warehouse_vehicle_stock as wv')
            ->join('vehicle_models as vm', 'wv.vehicle_model_id', '=', 'vm.id')
            ->join('vehicle_types as vt', 'vm.vehicle_type_id', '=', 'vt.id')
            ->where('wv.warehouse_id', $warehouseId)
            ->where('wv.current_stock', '>', 0)
            ->where('vm.status', 'active')
            ->select(
                'vm.id', 'vm.brand', 'vm.model_name', 'vm.model_code', 'vm.selling_price',
                'wv.current_stock', 'wv.reorder_level',
                'vt.id as type_id', 'vt.name as type_name'
            )
            ->orderBy('vt.name')
            ->orderBy('vm.brand')
            ->get();

        // Which vehicle_model_ids have at least one purchase batch with remaining stock
        $vehicleIdsWithBatches = DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->where('p.warehouse_id', $warehouseId)
            ->where('p.status', 'received')
            ->where('pi.item_type', 'vehicle')
            ->whereRaw('pi.quantity > pi.total_sold')
            ->pluck('pi.vehicle_model_id')
            ->unique()
            ->toArray();

        // Group vehicles by type — only include vehicles that have batches
        $vehicleTypes = [];
        foreach ($vehicles as $v) {
            if (!in_array($v->id, $vehicleIdsWithBatches)) continue;

            $typeId = $v->type_id;
            if (!isset($vehicleTypes[$typeId])) {
                $vehicleTypes[$typeId] = [
                    'id'     => $typeId,
                    'name'   => $v->type_name,
                    'models' => [],
                ];
            }
            $name = $v->brand . ' ' . $v->model_name;
            if ($v->model_code) $name .= ' (' . $v->model_code . ')';
            $vehicleTypes[$typeId]['models'][] = [
                'id'      => $v->id,
                'name'    => $name,
                'price'   => $v->selling_price,
                'stock'   => $v->current_stock,
                'reorder' => $v->reorder_level,
            ];
        }

        return response()->json([
            'vehicles'   => array_values($vehicleTypes),
            'categories' => array_values($categories),
        ]);
    }

    /**
     * AJAX: return available purchase batches (with remaining qty) for a given
     * item + warehouse. Used by the sale form PO# dropdown.
     *
     * GET /sales/ajax/purchase-batches?item_type=spare_part&item_id=5&warehouse_id=1
     */
    public function purchaseBatches(Request $request): \Illuminate\Http\JsonResponse
    {
        $itemType    = $request->item_type;
        $itemId      = (int) $request->item_id;
        $warehouseId = (int) $request->warehouse_id;

        if (!$itemType || !$itemId) {
            return response()->json([]);
        }

        // Validate warehouse access
        if ($warehouseId && !in_array($warehouseId, auth()->user()->accessibleWarehouseIds())) {
            return response()->json([]);
        }

        $column = $itemType === 'vehicle' ? 'vehicle_model_id' : 'spare_part_id';

        $batches = DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->where('pi.item_type', $itemType)
            ->where("pi.{$column}", $itemId)
            ->where('p.status', 'received')
            ->when($warehouseId, fn($q) => $q->where('p.warehouse_id', $warehouseId))
            ->whereRaw('pi.quantity > pi.total_sold')
            ->orderBy('p.purchase_date')
            ->orderBy('pi.id')
            ->select(
                'pi.id as purchase_item_id',
                'p.purchase_number',
                'p.purchase_date',
                'pi.quantity',
                'pi.total_sold',
                'pi.unit_price'
            )
            ->get()
            ->map(function ($row) {
                return [
                    'purchase_item_id' => $row->purchase_item_id,
                    'purchase_number'  => $row->purchase_number,
                    'purchase_date'    => $row->purchase_date,
                    'remaining'        => $row->quantity - $row->total_sold,
                    'unit_price'       => $row->unit_price,
                ];
            });

        return response()->json($batches);
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
