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
                'total_parts_value'    => \App\Services\StockService::partsStockValue([$warehouseId]),
                'total_vehicles_value' => \App\Services\StockService::vehiclesStockValue([$warehouseId]),
                'low_parts'    => DB::table('warehouse_spare_part_stock')->where('warehouse_id', $warehouseId)
                    ->where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'reorder_level')->count(),
                'out_parts'    => DB::table('warehouse_spare_part_stock')->where('warehouse_id', $warehouseId)
                    ->where('current_stock', '<=', 0)->count(),
                'low_vehicles' => DB::table('warehouse_vehicle_stock')->where('warehouse_id', $warehouseId)
                    ->where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'reorder_level')->count(),
                'out_vehicles' => DB::table('warehouse_vehicle_stock')->where('warehouse_id', $warehouseId)
                    ->where('current_stock', '<=', 0)->count(),
            ];

            // Attach stock value to each part row (purchase value - sale value for this part)
            $partIds  = $parts->pluck('id')->toArray();
            $valueMap = \App\Services\StockService::partsStockValueMap($partIds, [$warehouseId]);
            foreach ($parts as $part) {
                $part->stock_value = $valueMap[$part->id] ?? 0;
            }

            // Attach stock value to vehicle rows
            $vehicleIds    = $vehicles->pluck('id')->toArray();
            $vValueMap     = \App\Services\StockService::vehiclesStockValueMap($vehicleIds, [$warehouseId]);
            foreach ($vehicles as $v) {
                $v->stock_value = $vValueMap[$v->id] ?? 0;
            }

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
                'total_parts_value'    => \App\Services\StockService::partsStockValue(),
                'total_vehicles_value' => \App\Services\StockService::vehiclesStockValue(),
                'low_parts'     => SparePart::lowStock()->count(),
                'out_parts'     => SparePart::outOfStock()->count(),
                'low_vehicles'  => VehicleStock::whereColumn('current_stock', '<=', 'reorder_level')->count(),
                'out_vehicles'  => VehicleStock::where('current_stock', '<=', 0)->count(),
            ];

            $isWarehouseView = false;
        }

        // Attach stock value to parts for the Stock Value column in the view
        if ($parts instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $ids      = $parts->getCollection()->pluck('id')->toArray();
            $wIds     = $warehouseId ? [$warehouseId] : [];
            $valueMap = \App\Services\StockService::partsStockValueMap($ids, $wIds);
            $parts->getCollection()->each(function ($part) use ($valueMap) {
                $id = is_object($part) && isset($part->id) ? $part->id : ($part['id'] ?? null);
                $part->stock_value = $valueMap[$id] ?? ($part->stock_value ?? 0);
            });
        }

        // Attach stock value to vehicles for the Stock Value column
        if ($vehicles instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $vids      = $vehicles->getCollection()->map(fn($v) => isset($v->vehicleModel) ? $v->vehicleModel->id : ($v->id ?? null))->filter()->toArray();
            $wIds      = $warehouseId ? [$warehouseId] : [];
            $vValueMap = \App\Services\StockService::vehiclesStockValueMap($vids, $wIds);
            $vehicles->getCollection()->each(function ($vs) use ($vValueMap) {
                $vmId = isset($vs->vehicleModel) ? $vs->vehicleModel->id : ($vs->id ?? null);
                $vs->stock_value = $vValueMap[$vmId] ?? ($vs->stock_value ?? 0);
            });
        }

        $categories   = PartCategory::active()->orderBy('name')->get();
        $vehicleTypes = VehicleType::active()->get();

        return view('inventory.current-stock.index', compact(
            'parts', 'vehicles', 'summary', 'categories', 'vehicleTypes', 'tab',
            'warehouses', 'warehouseId', 'isWarehouseView'
        ));
    }
}
