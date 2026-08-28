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
        $user          = auth()->user();
        $accessibleIds = $user->accessibleWarehouseIds();

        $query = Purchase::with('supplier', 'user')
            ->whereIn('warehouse_id', $accessibleIds)
            ->where('purchase_type', 'purchase');  // exclude transfer-stub records

        // Non-admins only see their own purchases within their warehouses
        if (! $user->seesAllUsers()) {
            $query->where('user_id', $user->id);
        }

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

        $totalsQuery = Purchase::whereIn('warehouse_id', $accessibleIds)
            ->where('purchase_type', 'purchase');  // exclude transfer-stub records
        if (! $user->seesAllUsers()) {
            $totalsQuery->where('user_id', $user->id);
        }
        $totals = $totalsQuery->selectRaw('
            SUM(total) as grand_total,
            SUM(paid_amount) as grand_paid,
            SUM(balance) as grand_balance
        ')->first();

        return view('purchases.purchases.index', compact('purchases', 'totals'));
    }

    public function create()
    {
        $suppliers        = Supplier::active()->orderBy('name')->get();
        $vehicleTypes     = VehicleType::active()->with('activeVehicleModels.stock')->get();
        $categories       = PartCategory::active()->with('spareParts.unit')->orderBy('name')->get();
        $number           = Purchase::generateNumber();
        $warehouses       = auth()->user()->accessibleWarehouses()->get();
        $defaultWarehouse = $warehouses->firstWhere('is_default', true) ?? $warehouses->first();

        // Pre-encode JSON for JS (avoids PHP 8.5 parse issues with @json + arrow functions)

        // Total unsold per spare part: SUM(quantity - total_sold) from purchase_items
        $partUnsold = DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->where('pi.item_type', 'spare_part')
            ->where('p.status', 'received')
            ->selectRaw('pi.spare_part_id, SUM(pi.quantity - pi.total_sold) as unsold')
            ->groupBy('pi.spare_part_id')
            ->pluck('unsold', 'spare_part_id');

        // Total unsold per vehicle model
        $vehicleUnsold = DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->where('pi.item_type', 'vehicle')
            ->where('p.status', 'received')
            ->selectRaw('pi.vehicle_model_id, SUM(pi.quantity - pi.total_sold) as unsold')
            ->groupBy('pi.vehicle_model_id')
            ->pluck('unsold', 'vehicle_model_id');

        $vehicleTypesJson = json_encode($vehicleTypes->map(function ($vt) use ($vehicleUnsold) {
            return [
                'id'     => $vt->id,
                'name'   => $vt->name,
                'models' => $vt->activeVehicleModels->map(function ($m) use ($vehicleUnsold) {
                    return [
                        'id'     => $m->id,
                        'name'   => $m->brand . ' ' . $m->model_name . ($m->model_code ? ' (' . $m->model_code . ')' : ''),
                        'price'  => $m->buying_price,
                        'stock'  => $m->stock?->current_stock ?? 0,
                        'unsold' => (int) ($vehicleUnsold[$m->id] ?? 0),
                    ];
                })->values(),
            ];
        })->values());

        $categoriesJson = json_encode($categories->map(function ($cat) use ($partUnsold) {
            return [
                'id'    => $cat->id,
                'name'  => $cat->name,
                'parts' => $cat->spareParts->map(function ($p) use ($partUnsold) {
                    return [
                        'id'     => $p->id,
                        'name'   => $p->name . ' (' . $p->part_number . ')',
                        'price'  => $p->buying_price,
                        'stock'  => $p->current_stock,
                        'unsold' => (int) ($partUnsold[$p->id] ?? 0),
                        'unit'   => $p->unit->abbreviation,
                    ];
                })->values(),
            ];
        })->values());

        return view('purchases.purchases.create', compact('suppliers', 'vehicleTypes', 'categories', 'number', 'vehicleTypesJson', 'categoriesJson', 'warehouses', 'defaultWarehouse'));
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

        // Reject duplicate item_type + item_id combinations across rows.
        // Each item should appear only once — use quantity to buy more of the same item.
        $seenItems = [];
        foreach ($request->items as $index => $row) {
            $key = $row['item_type'] . ':' . $row['item_id'];
            if (isset($seenItems[$key])) {
                $rowNum = $index + 1;
                $dupNum = $seenItems[$key] + 1;
                $name = $row['item_type'] === 'spare_part'
                    ? \App\Models\SparePart::find($row['item_id'])?->name
                    : \App\Models\VehicleModel::find($row['item_id'])?->full_name;
                return back()
                    ->with('error', "Item \"{$name}\" appears in both row #{$dupNum} and row #{$rowNum}. Each item can only appear once — increase the quantity instead.")
                    ->withInput();
            }
            $seenItems[$key] = $index;
        }

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
                'warehouse_id'    => $request->warehouse_id ?: \App\Models\Warehouse::getDefault()?->id,
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
        $purchase->load('supplier', 'user', 'warehouse', 'items.vehicleModel.vehicleType', 'items.sparePart.category');

        // Load transfers that consumed batches from this purchase
        // Each source purchase_item links to a stock_transfer via the stub purchase
        $transferHistory = DB::table('purchase_items as dest_pi')
            ->join('purchases as dest_p',        'dest_pi.purchase_id',             '=', 'dest_p.id')
            ->join('stock_transfers as st',       'dest_p.stock_transfer_id',        '=', 'st.id')
            ->join('warehouses as to_wh',         'st.to_warehouse_id',              '=', 'to_wh.id')
            ->join('warehouses as from_wh',       'st.from_warehouse_id',            '=', 'from_wh.id')
            ->join('users as u',                  'st.user_id',                      '=', 'u.id')
            ->join('purchase_items as src_pi',    'dest_pi.source_purchase_item_id', '=', 'src_pi.id')
            ->where('src_pi.purchase_id', $purchase->id)   // only transfers FROM this purchase
            ->where('dest_pi.is_transfer', 1)
            ->select(
                'st.transfer_number',
                'st.transferred_at',
                'from_wh.name as from_warehouse',
                'to_wh.name as to_warehouse',
                'dest_pi.quantity as transferred_qty',
                'dest_pi.unit_price',
                'dest_pi.total_sold as sold_at_dest',
                'u.name as transferred_by',
                // item info
                'dest_pi.item_type',
                'dest_pi.spare_part_id',
                'dest_pi.vehicle_model_id'
            )
            ->orderByDesc('st.transferred_at')
            ->get();

        return view('purchases.purchases.show', compact('purchase', 'transferHistory'));
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

    public function edit(Purchase $purchase)
    {
        $suppliers = Supplier::active()->orderBy('name')->get();
        return view('purchases.purchases.edit', compact('purchase', 'suppliers'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $request->validate([
            'purchase_date'  => 'required|date',
            'due_date'       => 'nullable|date',
            'paid_amount'    => 'required|numeric|min:0',
            'notes'          => 'nullable|string|max:500',
        ]);

        $paid     = (float) $request->paid_amount;
        $total    = (float) $purchase->total;
        $balance  = max(0, $total - $paid);
        $payStatus = $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

        // Adjust supplier balance
        if ($purchase->balance != $balance) {
            $diff = $balance - $purchase->balance;
            if ($diff > 0) {
                $purchase->supplier->increment('balance', $diff);
            } elseif ($diff < 0) {
                $purchase->supplier->decrement('balance', abs($diff));
            }
        }

        $purchase->update([
            'purchase_date'  => $request->purchase_date,
            'due_date'       => $request->due_date ?: null,
            'paid_amount'    => $paid,
            'balance'        => $balance,
            'payment_status' => $payStatus,
            'notes'          => $request->notes,
        ]);

        return redirect()->route('purchases.show', $purchase)
            ->with('success', 'Purchase updated successfully.');
    }

    public function destroy(Purchase $purchase)
    {
        DB::beginTransaction();
        try {
            $purchase->load('items.vehicleModel', 'items.sparePart');

            // Reverse stock for each purchased item
            foreach ($purchase->items as $item) {
                if ($item->item_type === 'vehicle' && $item->vehicleModel) {
                    $this->stockService->decreaseVehicleStock(
                        $item->vehicleModel, $item->quantity, 'adjustment_out',
                        auth()->id(), $item->unit_price,
                        \App\Models\Purchase::class, $purchase->id,
                        "Deleted Purchase #{$purchase->purchase_number}",
                        $purchase->warehouse_id
                    );
                } elseif ($item->item_type === 'spare_part' && $item->sparePart) {
                    $this->stockService->decreasePartStock(
                        $item->sparePart, $item->quantity, 'adjustment_out',
                        auth()->id(), $item->unit_price,
                        \App\Models\Purchase::class, $purchase->id,
                        "Deleted Purchase #{$purchase->purchase_number}",
                        $purchase->warehouse_id
                    );
                }
            }

            // Reverse supplier balance
            if ($purchase->balance > 0) {
                $purchase->supplier->decrement('balance', $purchase->balance);
            }

            $purchase->delete(); // cascades to purchase_items

            DB::commit();
            return redirect()->route('purchases.index')
                ->with('success', "Purchase #{$purchase->purchase_number} deleted and stock reversed.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete purchase: ' . $e->getMessage());
        }
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
