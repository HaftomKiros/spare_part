<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SparePart;
use App\Models\Supplier;
use App\Models\VehicleModel;
use App\Models\VehicleStock;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString();
        $user  = auth()->user();

        // Scope available warehouses to what this user can access
        $accessibleIds = $user->accessibleWarehouseIds();
        $warehouses    = $user->accessibleWarehouses()->orderBy('name')->get();

        // Support multiple warehouse IDs via ?warehouse_ids[]=1&warehouse_ids[]=2
        // but clamp selection to only warehouses the user can access
        $warehouseIds = collect($request->input('warehouse_ids', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->intersect($accessibleIds)   // ← enforce scope
            ->values()
            ->toArray();

        // If no explicit filter chosen, default to the user's accessible warehouses
        if (empty($warehouseIds)) {
            $warehouseIds = $accessibleIds;
        }

        $hasFilter    = true; // always filtered now
        $filterLabels = $warehouses->whereIn('id', $warehouseIds)->pluck('name')->implode(', ');

        // Helper: apply warehouse filter to a query builder
        $wf = function ($q) use ($hasFilter, $warehouseIds) {
            if ($hasFilter) $q->whereIn('warehouse_id', $warehouseIds);
        };

        // ── Stat Cards ────────────────────────────────
        $stats = [
            'today_sales'       => Sale::whereDate('sale_date', $today)->where('status', 'completed')
                                       ->when($hasFilter, fn($q) => $q->whereIn('warehouse_id', $warehouseIds))->sum('total'),
            'today_sales_count' => Sale::whereDate('sale_date', $today)->where('status', 'completed')
                                       ->when($hasFilter, fn($q) => $q->whereIn('warehouse_id', $warehouseIds))->count(),
            'month_sales'       => Sale::whereYear('sale_date', now()->year)->whereMonth('sale_date', now()->month)
                                       ->where('status', 'completed')
                                       ->when($hasFilter, fn($q) => $q->whereIn('warehouse_id', $warehouseIds))->sum('total'),
            'month_purchases'   => Purchase::whereYear('purchase_date', now()->year)->whereMonth('purchase_date', now()->month)
                                            ->when($hasFilter, fn($q) => $q->whereIn('warehouse_id', $warehouseIds))->sum('total'),
            'total_vehicles'    => VehicleModel::active()->count(),
            'total_spare_parts' => SparePart::active()->count(),
            'total_customers'   => Customer::active()->count(),
            'total_suppliers'   => Supplier::active()->count(),
        ];

        // Stock & low-stock
        if ($hasFilter) {
            $stats['low_stock_parts'] = DB::table('warehouse_spare_part_stock')
                ->whereIn('warehouse_id', $warehouseIds)->whereColumn('current_stock', '<=', 'reorder_level')->count();
            $stats['low_stock_vehicles'] = DB::table('warehouse_vehicle_stock')
                ->whereIn('warehouse_id', $warehouseIds)->whereColumn('current_stock', '<=', 'reorder_level')->count();
            $stats['inventory_value_parts'] = 0; // price set per purchase, not catalog
            $stats['inventory_value_vehicles'] = DB::table('warehouse_vehicle_stock as wv')
                ->join('vehicle_models as vm', 'wv.vehicle_model_id', '=', 'vm.id')
                ->whereIn('wv.warehouse_id', $warehouseIds)
                ->sum(DB::raw('wv.current_stock * vm.buying_price'));
        } else {
            $stats['low_stock_parts']    = SparePart::lowStock()->count();
            $stats['low_stock_vehicles'] = VehicleStock::whereColumn('current_stock', '<=', 'reorder_level')->count();
            $stats['inventory_value_parts'] = 0; // price set per purchase, not catalog
            $stats['inventory_value_vehicles'] = DB::table('vehicle_stocks as vs')
                ->join('vehicle_models as vm', 'vs.vehicle_model_id', '=', 'vm.id')
                ->selectRaw('SUM(vs.current_stock * vm.buying_price) as val')->value('val') ?? 0;
        }
        $stats['total_inventory_value'] = $stats['inventory_value_parts'] + $stats['inventory_value_vehicles'];

        // Profit this month
        $profitQuery = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->where('s.status', 'completed')
            ->whereYear('s.sale_date', now()->year)
            ->whereMonth('s.sale_date', now()->month);
        if ($hasFilter) $profitQuery->whereIn('s.warehouse_id', $warehouseIds);
        $salesItems = $profitQuery->select('si.item_type', 'si.vehicle_model_id', 'si.spare_part_id', 'si.quantity', 'si.unit_price')->get();

        $profit = 0;
        foreach ($salesItems as $item) {
            if ($item->item_type === 'vehicle') {
                $cost = DB::table('vehicle_models')->where('id', $item->vehicle_model_id)->value('buying_price') ?? 0;
            } else {
                // Use the last purchase price for this spare part as cost basis
                $cost = DB::table('purchase_items')
                    ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
                    ->where('purchase_items.spare_part_id', $item->spare_part_id)
                    ->orderByDesc('purchases.purchase_date')
                    ->value('purchase_items.unit_price') ?? 0;
            }
            $profit += ($item->unit_price - $cost) * $item->quantity;
        }
        $stats['month_profit'] = $profit;

        // ── Charts: Sales last 7 days ────────────────
        $salesChart = Sale::selectRaw('DATE(sale_date) as date, SUM(total) as total, COUNT(*) as count')
            ->where('status', 'completed')
            ->when($hasFilter, fn($q) => $q->whereIn('warehouse_id', $warehouseIds))
            ->whereBetween('sale_date', [now()->subDays(6)->toDateString(), $today])
            ->groupBy('date')->orderBy('date')->get()->keyBy('date');

        $chartLabels = [];
        $chartData   = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $chartLabels[] = now()->subDays($i)->format('M d');
            $chartData[]   = $salesChart[$d]->total ?? 0;
        }

        // Sales mix
        $vehicleSales = Sale::completed()
            ->when($hasFilter, fn($q) => $q->whereIn('sales.warehouse_id', $warehouseIds))
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.item_type', 'vehicle')
            ->whereYear('sales.sale_date', now()->year)->whereMonth('sales.sale_date', now()->month)
            ->sum('sale_items.total');

        $partSales = Sale::completed()
            ->when($hasFilter, fn($q) => $q->whereIn('sales.warehouse_id', $warehouseIds))
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.item_type', 'spare_part')
            ->whereYear('sales.sale_date', now()->year)->whereMonth('sales.sale_date', now()->month)
            ->sum('sale_items.total');

        // Recent sales
        $recentSales = Sale::with('customer', 'user')
            ->where('status', 'completed')
            ->when($hasFilter, fn($q) => $q->whereIn('warehouse_id', $warehouseIds))
            ->latest()->limit(8)->get();

        // Low stock items
        if ($hasFilter) {
            $lowStockParts = SparePart::with('category', 'unit')
                ->whereHas('warehouses', fn($q) => $q->whereIn('warehouse_id', $warehouseIds)
                    ->whereColumn('warehouse_spare_part_stock.current_stock', '<=', 'warehouse_spare_part_stock.reorder_level'))
                ->active()->orderBy('current_stock')->limit(5)->get();

            $lowStockVehicles = collect(DB::table('warehouse_vehicle_stock as wv')
                ->join('vehicle_models as vm', 'wv.vehicle_model_id', '=', 'vm.id')
                ->join('vehicle_types as vt', 'vm.vehicle_type_id', '=', 'vt.id')
                ->whereIn('wv.warehouse_id', $warehouseIds)
                ->whereColumn('wv.current_stock', '<=', 'wv.reorder_level')
                ->orderBy('wv.current_stock')->limit(5)
                ->select('wv.*', 'vm.brand', 'vm.model_name', 'vm.model_code', 'vt.name as type_name')
                ->get());
        } else {
            $lowStockParts = SparePart::with('category', 'unit')
                ->lowStock()->active()->orderBy('current_stock')->limit(5)->get();
            $lowStockVehicles = VehicleStock::with('vehicleModel.vehicleType')
                ->whereColumn('current_stock', '<=', 'reorder_level')->orderBy('current_stock')->limit(5)->get();
        }

        // Recent purchases
        $recentPurchases = Purchase::with('supplier')
            ->when($hasFilter, fn($q) => $q->whereIn('warehouse_id', $warehouseIds))
            ->latest()->limit(5)->get();

        return view('dashboard.index', compact(
            'stats', 'warehouses', 'warehouseIds', 'hasFilter', 'filterLabels',
            'chartLabels', 'chartData',
            'vehicleSales', 'partSales',
            'recentSales', 'lowStockParts', 'lowStockVehicles',
            'recentPurchases'
        ) + [
            // Legacy compatibility — prevents crash if cached view still references $warehouse
            'warehouse'   => null,
            'warehouseId' => null,
        ]);
    }
}
