<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::withCount([])->get()->map(function ($w) {
            $w->parts_count    = DB::table('warehouse_spare_part_stock')->where('warehouse_id', $w->id)->count();
            $w->vehicles_count = DB::table('warehouse_vehicle_stock')->where('warehouse_id', $w->id)->count();
            $w->low_stock      = $w->low_stock_count;
            $w->stock_value    = $w->total_stock_value;
            return $w;
        });

        return view('settings.warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        $code = Warehouse::generateCode();
        return view('settings.warehouses.create', compact('code'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:150',
            'city'       => 'nullable|string|max:100',
            'address'    => 'nullable|string|max:300',
            'phone'      => 'nullable|string|max:30',
            'manager'    => 'nullable|string|max:100',
            'status'     => 'required|in:active,inactive',
            'is_default' => 'nullable|boolean',
            'notes'      => 'nullable|string|max:500',
        ]);

        $data['code']       = Warehouse::generateCode();
        $data['is_default'] = $request->boolean('is_default');

        // Only one default warehouse
        if ($data['is_default']) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        Warehouse::create($data);

        return redirect()->route('settings.warehouses.index')
            ->with('success', "Warehouse '{$data['name']}' created successfully.");
    }

    public function show(Warehouse $warehouse)
    {
        // Per-warehouse spare parts stock
        $parts = DB::table('warehouse_spare_part_stock as ws')
            ->join('spare_parts as sp', 'ws.spare_part_id', '=', 'sp.id')
            ->join('part_categories as pc', 'sp.part_category_id', '=', 'pc.id')
            ->join('units as u', 'sp.unit_id', '=', 'u.id')
            ->where('ws.warehouse_id', $warehouse->id)
            ->select('sp.id', 'sp.name', 'sp.part_number',
                     'pc.name as category', 'u.abbreviation as unit',
                     'ws.current_stock', 'ws.reorder_level')
            ->orderBy('ws.current_stock')
            ->get();

        // Per-warehouse vehicle stock
        $vehicles = DB::table('warehouse_vehicle_stock as wv')
            ->join('vehicle_models as vm', 'wv.vehicle_model_id', '=', 'vm.id')
            ->join('vehicle_types as vt', 'vm.vehicle_type_id', '=', 'vt.id')
            ->where('wv.warehouse_id', $warehouse->id)
            ->select('vm.id', 'vm.brand', 'vm.model_name', 'vm.model_code',
                     'vm.selling_price', 'vm.buying_price',
                     'vt.name as type_name',
                     'wv.current_stock', 'wv.reorder_level')
            ->orderBy('wv.current_stock')
            ->get();

        // Recent movements for this warehouse
        $movements = DB::table('stock_movements as sm')
            ->join('users as u', 'sm.user_id', '=', 'u.id')
            ->leftJoin('spare_parts as sp', 'sm.spare_part_id', '=', 'sp.id')
            ->leftJoin('vehicle_models as vm', 'sm.vehicle_model_id', '=', 'vm.id')
            ->where('sm.warehouse_id', $warehouse->id)
            ->select('sm.*', 'u.name as user_name',
                     'sp.name as part_name', 'sp.part_number',
                     'vm.brand', 'vm.model_name')
            ->orderByDesc('sm.created_at')
            ->limit(20)
            ->get();

        return view('settings.warehouses.show', compact('warehouse', 'parts', 'vehicles', 'movements'));
    }

    public function edit(Warehouse $warehouse)
    {
        return view('settings.warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:150',
            'city'       => 'nullable|string|max:100',
            'address'    => 'nullable|string|max:300',
            'phone'      => 'nullable|string|max:30',
            'manager'    => 'nullable|string|max:100',
            'status'     => 'required|in:active,inactive',
            'is_default' => 'nullable|boolean',
            'notes'      => 'nullable|string|max:500',
        ]);

        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default']) {
            Warehouse::where('is_default', true)
                ->where('id', '!=', $warehouse->id)
                ->update(['is_default' => false]);
        }

        $warehouse->update($data);

        return redirect()->route('settings.warehouses.index')
            ->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->is_default) {
            return back()->with('error', 'Cannot delete the default warehouse.');
        }

        $hasStock = DB::table('warehouse_spare_part_stock')
            ->where('warehouse_id', $warehouse->id)
            ->where('current_stock', '>', 0)
            ->exists();

        if (!$hasStock) {
            $hasStock = DB::table('warehouse_vehicle_stock')
                ->where('warehouse_id', $warehouse->id)
                ->where('current_stock', '>', 0)
                ->exists();
        }

        if ($hasStock) {
            return back()->with('error', 'Cannot delete: warehouse still has stock. Transfer or clear stock first.');
        }

        $warehouse->delete();
        return redirect()->route('settings.warehouses.index')
            ->with('success', 'Warehouse deleted.');
    }

    /**
     * Stock transfer between warehouses
     */
    public function transfer(Request $request)
    {
        $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id'   => 'required|exists:warehouses,id|different:from_warehouse_id',
            'item_type'         => 'required|in:spare_part,vehicle',
            'item_id'           => 'required|integer',
            'quantity'          => 'required|integer|min:1',
            'notes'             => 'nullable|string|max:300',
        ]);

        DB::transaction(function () use ($request) {
            $table  = $request->item_type === 'spare_part' ? 'warehouse_spare_part_stock' : 'warehouse_vehicle_stock';
            $col    = $request->item_type === 'spare_part' ? 'spare_part_id' : 'vehicle_model_id';
            $qty    = (int) $request->quantity;

            // Check source stock
            $from = DB::table($table)
                ->where('warehouse_id', $request->from_warehouse_id)
                ->where($col, $request->item_id)
                ->first();

            if (!$from || $from->current_stock < $qty) {
                throw new \Exception("Insufficient stock in source warehouse.");
            }

            // Deduct from source
            DB::table($table)
                ->where('warehouse_id', $request->from_warehouse_id)
                ->where($col, $request->item_id)
                ->decrement('current_stock', $qty);

            // Add to destination
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
                    'warehouse_id' => $request->to_warehouse_id,
                    $col           => $request->item_id,
                    'current_stock'=> $qty,
                    'reorder_level'=> 5,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            // Log both movements
            $userId = auth()->id();
            $base = [
                'item_type'      => $request->item_type,
                'spare_part_id'  => $request->item_type === 'spare_part' ? $request->item_id : null,
                'vehicle_model_id' => $request->item_type === 'vehicle'  ? $request->item_id : null,
                'quantity'       => $qty,
                'unit_cost'      => 0,
                'user_id'        => $userId,
                'notes'          => $request->notes ?? 'Stock transfer',
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            DB::table('stock_movements')->insert(array_merge($base, [
                'movement_type'   => 'adjustment_out',
                'warehouse_id'    => $request->from_warehouse_id,
                'quantity_before' => $from->current_stock,
                'quantity_after'  => $from->current_stock - $qty,
            ]));

            $toStock = $to ? $to->current_stock : 0;
            DB::table('stock_movements')->insert(array_merge($base, [
                'movement_type'   => 'adjustment_in',
                'warehouse_id'    => $request->to_warehouse_id,
                'quantity_before' => $toStock,
                'quantity_after'  => $toStock + $qty,
            ]));
        });

        return back()->with('success', 'Stock transferred successfully.');
    }
}
