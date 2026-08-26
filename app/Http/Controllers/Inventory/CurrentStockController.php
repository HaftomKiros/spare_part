<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PartCategory;
use App\Models\SparePart;
use App\Models\VehicleStock;
use App\Models\VehicleType;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CurrentStockController extends Controller
{
    public function index(Request $request)
    {
        $tab           = $request->get('tab', 'parts');
        $user          = auth()->user();
        $accessibleIds = $user->accessibleWarehouseIds();
        $warehouses    = $user->accessibleWarehouses()->get();

        // Clamp requested warehouse to user's accessible set
        $warehouseId = $request->warehouse_id ? (int) $request->warehouse_id : null;
        if ($warehouseId && ! in_array($warehouseId, $accessibleIds)) {
            $warehouseId = null;
        }

        // Default to first accessible warehouse when user has a specific assignment
        if (! $warehouseId && ! $user->isAdmin() && count($accessibleIds) === 1) {
            $warehouseId = $accessibleIds[0];
        }

        if ($warehouseId) {
            // ── Per-warehouse spare parts stock ──────────
            $partsQuery = DB::table('warehouse_spare_part_stock as ws')
                ->join('spare_parts as sp', 'ws.spare_part_id', '=', 'sp.id')
                ->join('part_categories as pc', 'sp.part_category_id', '=', 'pc.id')
                ->join('units as u', 'sp.unit_id', '=', 'u.id')
                ->where('ws.warehouse_id', $warehouseId)
                ->where('sp.status', 'active');

            if ($request->search) {
                $partsQuery->where(fn($q) =>
                    $q->where('sp.name', 'like', "%{$request->search}%")
                      ->orWhere('sp.part_number', 'like', "%{$request->search}%")
                );
            }
            if ($request->category) {
                $partsQuery->where('sp.part_category_id', $request->category);
            }
            if ($request->stock_filter === 'low') {
                $partsQuery->where('ws.current_stock', '>', 0)->whereColumn('ws.current_stock', '<=', 'ws.reorder_level');
            } elseif ($request->stock_filter === 'out') {
                $partsQuery->where('ws.current_stock', '<=', 0);
            } elseif ($request->stock_filter === 'ok') {
                $partsQuery->whereColumn('ws.current_stock', '>', 'ws.reorder_level');
            }

            $parts = $partsQuery
                ->selectRaw('sp.id, sp.name, sp.part_number, sp.buying_price, sp.selling_price, pc.name as category_name, u.abbreviation as unit_abbr, ws.current_stock, ws.reorder_level')
                ->orderBy('ws.current_stock')
                ->paginate(20, ['*'], 'parts_page')
                ->withQueryString();

            // ── Per-warehouse vehicle stock ───────────────
            $vehiclesQuery = DB::table('warehouse_vehicle_stock as wv')
                ->join('vehicle_models as vm', 'wv.vehicle_model_id', '=', 'vm.id')
                ->join('vehicle_types as vt', 'vm.vehicle_type_id', '=', 'vt.id')
                ->where('wv.warehouse_id', $warehouseId)
                ->where('vm.status', 'active');

            if ($request->search) {
                $vehiclesQuery->where(fn($q) =>
                    $q->where('vm.model_name', 'like', "%{$request->search}%")
                      ->orWhere('vm.model_code', 'like', "%{$request->search}%")
                );
            }
            if ($request->vehicle_type) {
                $vehiclesQuery->where('vm.vehicle_type_id', $request->vehicle_type);
            }
            if ($request->stock_filter === 'low') {
                $vehiclesQuery->where('wv.current_stock', '>', 0)->whereColumn('wv.current_stock', '<=', 'wv.reorder_level');
            } elseif ($request->stock_filter === 'out') {
                $vehiclesQuery->where('wv.current_stock', '<=', 0);
            } elseif ($request->stock_filter === 'ok') {
                $vehiclesQuery->whereColumn('wv.current_stock', '>', 'wv.reorder_level');
            }

            $vehicles = $vehiclesQuery
                ->selectRaw('vm.id, vm.brand, vm.model_name, vm.model_code, vm.buying_price, vm.selling_price, vt.name as type_name, wv.current_stock, wv.reorder_level')
                ->orderBy('wv.current_stock')
                ->paginate(20, ['*'], 'vehicles_page')
                ->withQueryString();

            // ── Per-warehouse summary ─────────────────────
            $summary = [
                'total_parts_value'    => DB::table('warehouse_spare_part_stock as ws')
                    ->join('spare_parts as sp', 'ws.spare_part_id', '=', 'sp.id')
                    ->where('ws.warehouse_id', $warehouseId)
                    ->sum(DB::raw('ws.current_stock * sp.buying_price')),
                'total_vehicles_value' => DB::table('warehouse_vehicle_stock as wv')
                    ->join('vehicle_models as vm', 'wv.vehicle_model_id', '=', 'vm.id')
                    ->where('wv.warehouse_id', $warehouseId)
                    ->sum(DB::raw('wv.current_stock * vm.buying_price')),
                'low_parts'    => DB::table('warehouse_spare_part_stock')->where('warehouse_id', $warehouseId)
                    ->where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'reorder_level')->count(),
                'out_parts'    => DB::table('warehouse_spare_part_stock')->where('warehouse_id', $warehouseId)
                    ->where('current_stock', '<=', 0)->count(),
                'low_vehicles' => DB::table('warehouse_vehicle_stock')->where('warehouse_id', $warehouseId)
                    ->where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'reorder_level')->count(),
                'out_vehicles' => DB::table('warehouse_vehicle_stock')->where('warehouse_id', $warehouseId)
                    ->where('current_stock', '<=', 0)->count(),
            ];

            $isWarehouseView = true;
        } else {
            // ── Global spare parts stock ──────────────────
            $partsQuery = SparePart::with('category', 'unit')->where('status', 'active');

            if ($request->search) {
                $partsQuery->where(fn($q) =>
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('part_number', 'like', "%{$request->search}%")
                );
            }
            if ($request->category) {
                $partsQuery->where('part_category_id', $request->category);
            }
            if ($request->stock_filter === 'low') {
                $partsQuery->lowStock();
            } elseif ($request->stock_filter === 'out') {
                $partsQuery->outOfStock();
            } elseif ($request->stock_filter === 'ok') {
                $partsQuery->whereColumn('current_stock', '>', 'reorder_level');
            }

            $parts = $partsQuery->orderBy('current_stock')->paginate(20, ['*'], 'parts_page')->withQueryString();

            // ── Global vehicle stock ──────────────────────
            $vehiclesQuery = VehicleStock::with('vehicleModel.vehicleType')
                ->whereHas('vehicleModel', fn($q) => $q->where('status', 'active'));

            if ($request->search) {
                $vehiclesQuery->whereHas('vehicleModel', fn($q) =>
                    $q->where('model_name', 'like', "%{$request->search}%")
                      ->orWhere('model_code', 'like', "%{$request->search}%")
                );
            }
            if ($request->vehicle_type) {
                $vehiclesQuery->whereHas('vehicleModel', fn($q) => $q->where('vehicle_type_id', $request->vehicle_type));
            }
            if ($request->stock_filter === 'low') {
                $vehiclesQuery->whereColumn('current_stock', '<=', 'reorder_level');
            } elseif ($request->stock_filter === 'out') {
                $vehiclesQuery->where('current_stock', '<=', 0);
            } elseif ($request->stock_filter === 'ok') {
                $vehiclesQuery->whereColumn('current_stock', '>', 'reorder_level');
            }

            $vehicles = $vehiclesQuery->orderBy('current_stock')->paginate(20, ['*'], 'vehicles_page')->withQueryString();

            $summary = [
                'total_parts_value'    => SparePart::selectRaw('SUM(current_stock * buying_price)')->value('SUM(current_stock * buying_price)') ?? 0,
                'total_vehicles_value' => VehicleStock::join('vehicle_models','vehicle_stocks.vehicle_model_id','=','vehicle_models.id')
                                            ->selectRaw('SUM(vehicle_stocks.current_stock * vehicle_models.buying_price)')->value('SUM(vehicle_stocks.current_stock * vehicle_models.buying_price)') ?? 0,
                'low_parts'     => SparePart::lowStock()->count(),
                'out_parts'     => SparePart::outOfStock()->count(),
                'low_vehicles'  => VehicleStock::whereColumn('current_stock', '<=', 'reorder_level')->count(),
                'out_vehicles'  => VehicleStock::where('current_stock', '<=', 0)->count(),
            ];

            $isWarehouseView = false;
        }

        $categories   = PartCategory::active()->orderBy('name')->get();
        $vehicleTypes = VehicleType::active()->get();

        return view('inventory.current-stock.index', compact(
            'parts', 'vehicles', 'summary', 'categories', 'vehicleTypes', 'tab',
            'warehouses', 'warehouseId', 'isWarehouseView'
        ));
    }
}
