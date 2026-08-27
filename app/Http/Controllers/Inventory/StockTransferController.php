<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\SparePart;
use App\Models\VehicleModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $user          = auth()->user();
        $accessibleIds = $user->accessibleWarehouseIds();

        // Show transfers where the user's warehouse appears as FROM or TO
        // Also scope to transfers made by this user for non-admins
        $query = DB::table('stock_movements as sm')
            ->join('warehouses as w', 'sm.warehouse_id', '=', 'w.id')
            ->join('users as u', 'sm.user_id', '=', 'u.id')
            ->leftJoin('spare_parts as sp', 'sm.spare_part_id', '=', 'sp.id')
            ->leftJoin('vehicle_models as vm', 'sm.vehicle_model_id', '=', 'vm.id')
            ->whereIn('sm.movement_type', ['adjustment_in', 'adjustment_out'])
            ->whereNotNull('sm.notes')
            ->where('sm.notes', 'like', '%transfer%')
            ->where(function ($q) use ($accessibleIds) {
                // Show if this warehouse is source OR destination
                $q->whereIn('sm.warehouse_id', $accessibleIds);
            });

        // Non-admins: only see transfers they initiated
        if (! $user->seesAllUsers()) {
            $query->where('sm.user_id', $user->id);
        }

        if ($request->warehouse_id && in_array((int)$request->warehouse_id, $accessibleIds)) {
            $query->where('sm.warehouse_id', $request->warehouse_id);
        }
        if ($request->date_from) {
            $query->whereDate('sm.created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('sm.created_at', '<=', $request->date_to);
        }

        $movements = $query
            ->select('sm.*', 'w.name as warehouse_name', 'u.name as user_name',
                     'sp.name as part_name', 'sp.part_number',
                     'vm.brand', 'vm.model_name')
            ->orderByDesc('sm.created_at')
            ->paginate(20)
            ->withQueryString();

        $warehouses = auth()->user()->accessibleWarehouses()->get();

        return view('inventory.transfers.index', compact('movements', 'warehouses'));
    }

    public function create()
    {
        $warehouses = auth()->user()->accessibleWarehouses()->get();
        $parts      = SparePart::active()->with('unit')->orderBy('name')->get();
        $vehicles   = VehicleModel::active()->with('vehicleType')->orderBy('brand')->get();

        return view('inventory.transfers.create', compact('warehouses', 'parts', 'vehicles'));
    }

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

        // Check available stock in source warehouse
        $table = $request->item_type === 'spare_part' ? 'warehouse_spare_part_stock' : 'warehouse_vehicle_stock';
        $col   = $request->item_type === 'spare_part' ? 'spare_part_id' : 'vehicle_model_id';
        $qty   = (int) $request->quantity;

        $from = DB::table($table)
            ->where('warehouse_id', $request->from_warehouse_id)
            ->where($col, $request->item_id)
            ->first();

        $available = $from?->current_stock ?? 0;

        if ($available <= 0) {
            return back()
                ->with('error', 'Transfer not possible — the source warehouse has no stock for this item (0 units).')
                ->withInput();
        }

        if ($qty > $available) {
            return back()
                ->with('error', "Insufficient stock. You requested {$qty} unit(s) but the source warehouse only has {$available} unit(s).")
                ->withInput();
        }

        DB::transaction(function () use ($request, $table, $col, $qty, $from, $available) {
            $userId = auth()->id();
            $notes  = $request->notes ? 'Stock transfer — ' . $request->notes : 'Stock transfer';

            // Deduct from source
            DB::table($table)
                ->where('warehouse_id', $request->from_warehouse_id)
                ->where($col, $request->item_id)
                ->decrement('current_stock', $qty);

            // Add to destination (create record if doesn't exist)
            $to = DB::table($table)
                ->where('warehouse_id', $request->to_warehouse_id)
                ->where($col, $request->item_id)
                ->first();

            if ($to) {
                DB::table($table)
                    ->where('warehouse_id', $request->to_warehouse_id)
                    ->where($col, $request->item_id)
                    ->increment('current_stock', $qty);
            } else {
                DB::table($table)->insert([
                    'warehouse_id'  => $request->to_warehouse_id,
                    $col            => $request->item_id,
                    'current_stock' => $qty,
                    'reorder_level' => $request->item_type === 'spare_part' ? 5 : 2,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }

            $toStock = $to ? $to->current_stock : 0;
            $base = [
                'item_type'        => $request->item_type,
                'spare_part_id'    => $request->item_type === 'spare_part' ? $request->item_id : null,
                'vehicle_model_id' => $request->item_type === 'vehicle'    ? $request->item_id : null,
                'quantity'         => $qty,
                'unit_cost'        => 0,
                'user_id'          => $userId,
                'notes'            => $notes,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];

            // Log outward from source
            DB::table('stock_movements')->insert(array_merge($base, [
                'movement_type'   => 'adjustment_out',
                'warehouse_id'    => $request->from_warehouse_id,
                'quantity_before' => $available,
                'quantity_after'  => $available - $qty,
            ]));

            // Log inward to destination
            DB::table('stock_movements')->insert(array_merge($base, [
                'movement_type'   => 'adjustment_in',
                'warehouse_id'    => $request->to_warehouse_id,
                'quantity_before' => $toStock,
                'quantity_after'  => $toStock + $qty,
            ]));
        });

        $fromName = Warehouse::find($request->from_warehouse_id)->name;
        $toName   = Warehouse::find($request->to_warehouse_id)->name;

        return redirect()->route('inventory.transfers.index')
            ->with('success', "Transferred {$qty} unit(s) from {$fromName} to {$toName} successfully.");
    }

    /**
     * AJAX: get stock for an item in a specific warehouse
     */
    public function warehouseStock(Request $request)
    {
        $warehouseId = (int) $request->warehouse_id;
        $itemType    = $request->item_type;
        $itemId      = (int) $request->item_id;

        if (!$warehouseId || !$itemType || !$itemId) {
            return response()->json(['stock' => 0]);
        }

        if ($itemType === 'spare_part') {
            $stock = DB::table('warehouse_spare_part_stock')
                ->where('warehouse_id', $warehouseId)
                ->where('spare_part_id', $itemId)
                ->value('current_stock') ?? 0;
        } else {
            $stock = DB::table('warehouse_vehicle_stock')
                ->where('warehouse_id', $warehouseId)
                ->where('vehicle_model_id', $itemId)
                ->value('current_stock') ?? 0;
        }

        return response()->json(['stock' => (int) $stock]);
    }

    /**
     * AJAX: return parts/vehicles that have current_stock > 0 in a given warehouse.
     * Used by the transfer form to filter the item dropdown.
     */
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

            // Only keep parts that have unsold purchase batches in this warehouse
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
                    'label' => $p->name . ' (' . $p->part_number . ') — ' . $p->unit . ' — Unsold: ' . (int)$unsoldMap[$p->id],
                    'stock' => $p->current_stock,
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

            // Only keep vehicles that have unsold purchase batches in this warehouse
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
                    'label' => $v->brand . ' ' . $v->model_name . ($v->model_code ? ' (' . $v->model_code . ')' : '') . ' — ' . $v->type_name . ' — Unsold: ' . (int)$unsoldMap[$v->id],
                    'stock' => $v->current_stock,
                    'unsold'=> (int) $unsoldMap[$v->id],
                ])->values();
        }

        return response()->json($items);
    }
}
