<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\SparePart;
use App\Models\StockTransfer;
use App\Models\VehicleModel;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    // ── Index ──────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $user          = auth()->user();
        $accessibleIds = $user->accessibleWarehouseIds();

        $query = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'user'])
            ->where(function ($q) use ($accessibleIds) {
                $q->whereIn('from_warehouse_id', $accessibleIds)
                  ->orWhereIn('to_warehouse_id', $accessibleIds);
            });

        if (! $user->seesAllUsers()) {
            $query->where('user_id', $user->id);
        }

        if ($request->warehouse_id && in_array((int) $request->warehouse_id, $accessibleIds)) {
            $wid = (int) $request->warehouse_id;
            $query->where(fn($q) => $q->where('from_warehouse_id', $wid)->orWhere('to_warehouse_id', $wid));
        }
        if ($request->date_from) {
            $query->whereDate('transferred_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('transferred_at', '<=', $request->date_to);
        }

        // Eager-load item summary: aggregate transferred items per transfer
        $transfers = $query->orderByDesc('transferred_at')->paginate(20)->withQueryString();

        // Load item lines for each transfer (via the stub purchase_items)
        $transferIds = $transfers->pluck('id');
        $itemLines   = DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->leftJoin('spare_parts as sp', 'pi.spare_part_id', '=', 'sp.id')
            ->leftJoin('vehicle_models as vm', 'pi.vehicle_model_id', '=', 'vm.id')
            ->whereNotNull('p.stock_transfer_id')
            ->whereIn('p.stock_transfer_id', $transferIds)
            ->select(
                'p.stock_transfer_id',
                'pi.item_type',
                'pi.quantity',
                'pi.unit_price',
                'pi.total_sold',
                'sp.name as part_name', 'sp.part_number',
                'vm.brand', 'vm.model_name', 'vm.model_code'
            )
            ->get()
            ->groupBy('stock_transfer_id');

        $warehouses = auth()->user()->accessibleWarehouses()->get();

        return view('inventory.transfers.index', compact('transfers', 'itemLines', 'warehouses'));
    }

    // ── Create ─────────────────────────────────────────────────────────────
    public function create()
    {
        $warehouses = auth()->user()->accessibleWarehouses()->get();
        $parts      = SparePart::active()->with('unit')->orderBy('name')->get();
        $vehicles   = VehicleModel::active()->with('vehicleType')->orderBy('brand')->get();

        return view('inventory.transfers.create', compact('warehouses', 'parts', 'vehicles'));
    }

    // ── Store — the new FIFO batch-cloning algorithm ───────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id'   => 'required|exists:warehouses,id|different:from_warehouse_id',
            'item_type'         => 'required|in:spare_part,vehicle',
            'item_id'           => 'required|integer',
            'quantity'          => 'required|integer|min:1',
            'notes'             => 'nullable|string|max:300',
        ], [
            'to_warehouse_id.different' => 'Source and destination warehouse must be different.',
        ]);

        $stockTable = $request->item_type === 'spare_part'
            ? 'warehouse_spare_part_stock'
            : 'warehouse_vehicle_stock';
        $col = $request->item_type === 'spare_part' ? 'spare_part_id' : 'vehicle_model_id';
        $qty = (int) $request->quantity;

        // ── Pre-flight: check physical stock in source ─────────────────────
        $fromStock = DB::table($stockTable)
            ->where('warehouse_id', $request->from_warehouse_id)
            ->where($col, $request->item_id)
            ->value('current_stock') ?? 0;

        if ($fromStock <= 0) {
            return back()
                ->with('error', 'Transfer not possible — the source warehouse has no stock for this item.')
                ->withInput();
        }
        if ($qty > $fromStock) {
            return back()
                ->with('error', "Insufficient stock. Requested {$qty} but only {$fromStock} available.")
                ->withInput();
        }

        // ── Pre-flight: check sellable batches cover the qty ───────────────
        // Only non-transfer purchase_items are consumed from the source.
        $sourceBatches = DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->where('pi.item_type', $request->item_type)
            ->where("pi.{$col}", $request->item_id)
            ->where('p.warehouse_id', $request->from_warehouse_id)
            ->where('p.status', 'received')
            ->where('p.purchase_type', 'purchase')   // only real purchases, not transfer stubs
            ->whereRaw('pi.quantity > pi.total_sold')
            ->orderBy('p.purchase_date')
            ->orderBy('pi.id')
            ->select('pi.id', 'pi.quantity', 'pi.total_sold', 'pi.unit_price')
            ->get();

        $totalAvailableInBatches = $sourceBatches->sum(fn($b) => $b->quantity - $b->total_sold);
        if ($totalAvailableInBatches < $qty) {
            return back()
                ->with('error', "Only {$totalAvailableInBatches} unit(s) available across purchase batches (physical stock: {$fromStock}). Cannot transfer {$qty}.")
                ->withInput();
        }

        $fromWarehouse = Warehouse::findOrFail($request->from_warehouse_id);
        $toWarehouse   = Warehouse::findOrFail($request->to_warehouse_id);

        DB::transaction(function () use (
            $request, $stockTable, $col, $qty, $fromStock,
            $fromWarehouse, $toWarehouse, $sourceBatches
        ) {
            $userId = auth()->id();
            $notes  = $request->notes ?? null;

            // ── 1. Create the stock_transfer header record ─────────────────
            $transfer = StockTransfer::create([
                'transfer_number'   => StockTransfer::generateNumber(),
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id'   => $toWarehouse->id,
                'user_id'           => $userId,
                'notes'             => $notes,
                'transferred_at'    => now(),
            ]);

            // ── 2. Find or create the transfer-stub Purchase for destination ─
            //    One stub purchase per transfer (grouped by transfer header).
            $stubPurchase = Purchase::create([
                'purchase_number'   => $transfer->transfer_number,
                'supplier_id'       => null,   // set after FIFO loop (inherited from source)
                'user_id'           => $userId,
                'warehouse_id'      => $toWarehouse->id,
                'purchase_date'     => now()->toDateString(),
                'due_date'          => null,
                'subtotal'          => 0,  // will update below
                'discount'          => 0,
                'tax'               => 0,
                'total'             => 0,  // will update below
                'paid_amount'       => 0,
                'balance'           => 0,
                'payment_status'    => 'paid',
                'status'            => 'received',
                'notes'             => "Stock transfer from {$fromWarehouse->name} — {$transfer->transfer_number}",
                'purchase_type'     => 'transfer',
                'stock_transfer_id' => $transfer->id,
            ]);

            // ── 3. FIFO: consume source batches and clone to destination ────
            $remaining       = $qty;
            $stubSubtotal    = 0;
            $stubSupplierId  = null;   // inherit supplier from the first source batch's purchase
            $toStockBefore   = DB::table($stockTable)
                ->where('warehouse_id', $toWarehouse->id)
                ->where($col, $request->item_id)
                ->value('current_stock') ?? 0;

            // Track how much value is deducted per source purchase
            // so we can reduce their subtotal/total/paid_amount accordingly
            $sourcePurchaseDeductions = []; // [ purchase_id => amount ]

            foreach ($sourceBatches as $batch) {
                if ($remaining <= 0) break;

                $available = $batch->quantity - $batch->total_sold;
                $take      = min($remaining, $available);

                // a) Mark sold on the source batch
                DB::table('purchase_items')
                    ->where('id', $batch->id)
                    ->update([
                        'total_sold' => $batch->total_sold + $take,
                        'updated_at' => now(),
                    ]);

                // b) Clone batch to destination warehouse (stub purchase)
                $lineTotal = round($take * $batch->unit_price, 2);
                PurchaseItem::create([
                    'purchase_id'             => $stubPurchase->id,
                    'item_type'               => $request->item_type,
                    'vehicle_model_id'        => $request->item_type === 'vehicle'    ? $request->item_id : null,
                    'spare_part_id'           => $request->item_type === 'spare_part' ? $request->item_id : null,
                    'quantity'                => $take,
                    'total_sold'              => 0,
                    'unit_price'              => $batch->unit_price,
                    'discount'                => 0,
                    'total'                   => $lineTotal,
                    'is_transfer'             => true,
                    'source_purchase_item_id' => $batch->id,
                ]);

                // Track deduction per source purchase
                $sourcePurchaseDeductions[$batch->purchase_id] =
                    ($sourcePurchaseDeductions[$batch->purchase_id] ?? 0) + $lineTotal;

                $stubSubtotal += $lineTotal;
                $remaining    -= $take;

                // Inherit supplier from first source batch
                if ($stubSupplierId === null) {
                    $stubSupplierId = DB::table('purchases')
                        ->where('id', $batch->purchase_id)
                        ->value('supplier_id');
                }
            }

            // c) Reduce source purchase(s) subtotal/total/paid_amount by transferred value
            foreach ($sourcePurchaseDeductions as $sourcePurchaseId => $deductAmount) {
                $sourcePo = DB::table('purchases')->where('id', $sourcePurchaseId)->first();
                if ($sourcePo) {
                    $newSubtotal    = max(0, round($sourcePo->subtotal    - $deductAmount, 2));
                    $newTotal       = max(0, round($sourcePo->total       - $deductAmount, 2));
                    $newPaidAmount  = max(0, round($sourcePo->paid_amount - $deductAmount, 2));
                    $newBalance     = max(0, round($sourcePo->balance     - $deductAmount, 2));
                    DB::table('purchases')->where('id', $sourcePurchaseId)->update([
                        'subtotal'       => $newSubtotal,
                        'total'          => $newTotal,
                        'paid_amount'    => $newPaidAmount,
                        'balance'        => $newBalance,
                        'payment_status' => $newTotal <= 0 || $newBalance <= 0 ? 'paid' : ($newPaidAmount > 0 ? 'partial' : 'unpaid'),
                        'updated_at'     => now(),
                    ]);
                }
            }

            // d) Update stub purchase with totals, supplier, and paid amount
            $stubPurchase->update([
                'subtotal'       => $stubSubtotal,
                'total'          => $stubSubtotal,
                'paid_amount'    => $stubSubtotal,  // already paid to supplier via original PO
                'balance'        => 0,
                'payment_status' => 'paid',
                'supplier_id'    => $stubSupplierId,  // same supplier as source
            ]);

            // ── 4. Update warehouse stock counters ─────────────────────────
            // Deduct from source
            DB::table($stockTable)
                ->where('warehouse_id', $fromWarehouse->id)
                ->where($col, $request->item_id)
                ->decrement('current_stock', $qty);

            // Add to destination (upsert)
            $destRow = DB::table($stockTable)
                ->where('warehouse_id', $toWarehouse->id)
                ->where($col, $request->item_id)
                ->first();

            if ($destRow) {
                DB::table($stockTable)
                    ->where('warehouse_id', $toWarehouse->id)
                    ->where($col, $request->item_id)
                    ->increment('current_stock', $qty);
            } else {
                DB::table($stockTable)->insert([
                    'warehouse_id'  => $toWarehouse->id,
                    $col            => $request->item_id,
                    'current_stock' => $qty,
                    'reorder_level' => $request->item_type === 'spare_part' ? 5 : 2,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }

            // ── 5. Log stock movements (transfer_out / transfer_in) ────────
            $baseMovement = [
                'item_type'        => $request->item_type,
                'spare_part_id'    => $request->item_type === 'spare_part' ? $request->item_id : null,
                'vehicle_model_id' => $request->item_type === 'vehicle'    ? $request->item_id : null,
                'quantity'         => $qty,
                'unit_cost'        => round($stubSubtotal / $qty, 4), // weighted avg cost
                'reference_type'   => StockTransfer::class,
                'reference_id'     => $transfer->id,
                'user_id'          => $userId,
                'notes'            => $notes ? "Transfer: {$notes}" : "Transfer #{$transfer->transfer_number}",
                'created_at'       => now(),
                'updated_at'       => now(),
            ];

            DB::table('stock_movements')->insert(array_merge($baseMovement, [
                'movement_type'   => 'transfer_out',
                'warehouse_id'    => $fromWarehouse->id,
                'quantity_before' => $fromStock,
                'quantity_after'  => $fromStock - $qty,
            ]));

            DB::table('stock_movements')->insert(array_merge($baseMovement, [
                'movement_type'   => 'transfer_in',
                'warehouse_id'    => $toWarehouse->id,
                'quantity_before' => $toStockBefore,
                'quantity_after'  => $toStockBefore + $qty,
            ]));
        });

        return redirect()->route('inventory.transfers.index')
            ->with('success', "Transferred {$qty} unit(s) from {$fromWarehouse->name} to {$toWarehouse->name} successfully.");
    }

    // ── AJAX: get stock for an item in a specific warehouse ────────────────
    public function warehouseStock(Request $request)
    {
        $warehouseId = (int) $request->warehouse_id;
        $itemType    = $request->item_type;
        $itemId      = (int) $request->item_id;

        if (!$warehouseId || !$itemType || !$itemId) {
            return response()->json(['stock' => 0]);
        }

        $table = $itemType === 'spare_part' ? 'warehouse_spare_part_stock' : 'warehouse_vehicle_stock';
        $col   = $itemType === 'spare_part' ? 'spare_part_id' : 'vehicle_model_id';

        $stock = DB::table($table)
            ->where('warehouse_id', $warehouseId)
            ->where($col, $itemId)
            ->value('current_stock') ?? 0;

        return response()->json(['stock' => (int) $stock]);
    }

    // ── AJAX: items available for transfer from a warehouse ────────────────
    public function warehouseItems(Request $request): \Illuminate\Http\JsonResponse
    {
        $warehouseId = (int) $request->warehouse_id;
        $type        = $request->item_type ?? 'spare_part';

        if (!$warehouseId) return response()->json([]);

        if (!in_array($warehouseId, auth()->user()->accessibleWarehouseIds())) {
            return response()->json([]);
        }

        if ($type === 'spare_part') {
            $items = DB::table('warehouse_spare_part_stock as ws')
                ->join('spare_parts as sp', 'ws.spare_part_id', '=', 'sp.id')
                ->join('units as u', 'sp.unit_id', '=', 'u.id')
                ->where('ws.warehouse_id', $warehouseId)
                ->where('ws.current_stock', '>', 0)
                ->where('sp.status', 'active')
                ->orderBy('sp.name')
                ->select('sp.id', 'sp.name', 'sp.part_number', 'u.abbreviation as unit', 'ws.current_stock')
                ->get();

            // Unsold batches — only real purchase batches (not transfer stubs),
            // because stubs at this warehouse may themselves become sources.
            // We count all remaining batches (real + transfer) for accuracy.
            $unsoldMap = DB::table('purchase_items as pi')
                ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
                ->where('p.warehouse_id', $warehouseId)
                ->where('p.status', 'received')
                ->where('pi.item_type', 'spare_part')
                ->whereRaw('pi.quantity > pi.total_sold')
                ->selectRaw('pi.spare_part_id, SUM(pi.quantity - pi.total_sold) as unsold')
                ->groupBy('pi.spare_part_id')
                ->pluck('unsold', 'spare_part_id');

            $items = $items
                ->filter(fn($p) => isset($unsoldMap[$p->id]) && $unsoldMap[$p->id] > 0)
                ->map(fn($p) => [
                    'id'    => $p->id,
                    'label' => $p->name . ' (' . $p->part_number . ') — ' . $p->unit . ' — Available: ' . (int) $unsoldMap[$p->id],
                    'stock' => (int) $unsoldMap[$p->id],
                    'unsold'=> (int) $unsoldMap[$p->id],
                ])->values();
        } else {
            $items = DB::table('warehouse_vehicle_stock as wv')
                ->join('vehicle_models as vm', 'wv.vehicle_model_id', '=', 'vm.id')
                ->join('vehicle_types as vt', 'vm.vehicle_type_id', '=', 'vt.id')
                ->where('wv.warehouse_id', $warehouseId)
                ->where('wv.current_stock', '>', 0)
                ->where('vm.status', 'active')
                ->orderBy('vm.brand')
                ->select('vm.id', 'vm.brand', 'vm.model_name', 'vm.model_code', 'vt.name as type_name', 'wv.current_stock')
                ->get();

            $unsoldMap = DB::table('purchase_items as pi')
                ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
                ->where('p.warehouse_id', $warehouseId)
                ->where('p.status', 'received')
                ->where('pi.item_type', 'vehicle')
                ->whereRaw('pi.quantity > pi.total_sold')
                ->selectRaw('pi.vehicle_model_id, SUM(pi.quantity - pi.total_sold) as unsold')
                ->groupBy('pi.vehicle_model_id')
                ->pluck('unsold', 'vehicle_model_id');

            $items = $items
                ->filter(fn($v) => isset($unsoldMap[$v->id]) && $unsoldMap[$v->id] > 0)
                ->map(fn($v) => [
                    'id'    => $v->id,
                    'label' => $v->brand . ' ' . $v->model_name . ($v->model_code ? ' (' . $v->model_code . ')' : '')
                               . ' — ' . $v->type_name . ' — Available: ' . (int) $unsoldMap[$v->id],
                    'stock' => (int) $unsoldMap[$v->id],
                    'unsold'=> (int) $unsoldMap[$v->id],
                ])->values();
        }

        return response()->json($items);
    }
}
