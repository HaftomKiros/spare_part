<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PartCategory;
use App\Models\SparePart;
use App\Models\VehicleStock;
use App\Models\VehicleType;
use Illuminate\Http\Request;

class CurrentStockController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'parts');

        // ── Spare Parts stock ─────────────────────────
        $partsQuery = SparePart::with('category', 'unit')
            ->where('status', 'active');

        if ($request->search) {
            $partsQuery->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('part_number', 'like', "%{$request->search}%");
            });
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

        $parts = $partsQuery->orderBy('current_stock')->paginate(20, ['*'], 'parts_page')
                            ->withQueryString();

        // ── Vehicle stock ─────────────────────────────
        $vehiclesQuery = VehicleStock::with('vehicleModel.vehicleType')
            ->whereHas('vehicleModel', fn($q) => $q->where('status', 'active'));

        if ($request->search) {
            $vehiclesQuery->whereHas('vehicleModel', fn($q) =>
                $q->where('model_name', 'like', "%{$request->search}%")
                  ->orWhere('model_code', 'like', "%{$request->search}%")
            );
        }
        if ($request->vehicle_type) {
            $vehiclesQuery->whereHas('vehicleModel', fn($q) =>
                $q->where('vehicle_type_id', $request->vehicle_type)
            );
        }
        if ($request->stock_filter === 'low') {
            $vehiclesQuery->whereColumn('current_stock', '<=', 'reorder_level');
        } elseif ($request->stock_filter === 'out') {
            $vehiclesQuery->where('current_stock', '<=', 0);
        } elseif ($request->stock_filter === 'ok') {
            $vehiclesQuery->whereColumn('current_stock', '>', 'reorder_level');
        }

        $vehicles = $vehiclesQuery->orderBy('current_stock')->paginate(20, ['*'], 'vehicles_page')
                                  ->withQueryString();

        // ── Summary ───────────────────────────────────
        $summary = [
            'total_parts_value'    => SparePart::selectRaw('SUM(current_stock * buying_price)')->value('SUM(current_stock * buying_price)') ?? 0,
            'total_vehicles_value' => VehicleStock::join('vehicle_models','vehicle_stocks.vehicle_model_id','=','vehicle_models.id')
                                        ->selectRaw('SUM(vehicle_stocks.current_stock * vehicle_models.buying_price)')->value('SUM(vehicle_stocks.current_stock * vehicle_models.buying_price)') ?? 0,
            'low_parts'     => SparePart::lowStock()->count(),
            'out_parts'     => SparePart::outOfStock()->count(),
            'low_vehicles'  => VehicleStock::whereColumn('current_stock', '<=', 'reorder_level')->count(),
            'out_vehicles'  => VehicleStock::where('current_stock', '<=', 0)->count(),
        ];

        $categories   = PartCategory::active()->orderBy('name')->get();
        $vehicleTypes = VehicleType::active()->get();

        return view('inventory.current-stock.index', compact(
            'parts', 'vehicles', 'summary', 'categories', 'vehicleTypes', 'tab'
        ));
    }
}
