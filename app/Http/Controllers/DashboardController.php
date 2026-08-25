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
        $today      = now()->toDateString();
        $warehouses = Warehouse::active()->get();
        $warehouseId = $request->warehouse_id ? (int) $request->warehouse_id : null;
        $warehouse   = $warehouseId ? Warehouse::find($warehouseId) : null;

        // Helper closure: applies optional warehouse filter to Sale queries
        $saleScope = function ($q) use ($warehouseId) {
            if ($warehouseId) $q->where('warehouse_id', $warehouseId);
        };
        $purchaseScope = function ($q) use ($warehouseId) {
            if ($warehouseId) $q->where('warehouse_id', $warehouseId);
        };

        // ── Stat Cards ────────────────────────────────
        $stats = [
            'today_sales'       => Sale::whereDate('sale_date', $today)->where('status', 'completed')
                                       ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))->sum('total'),
            'today_sales_count' => Sale::whereDate('sale_date', $today)->where('status', 'completed')
                                       ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))->count(),
            'month_sales'       => Sale::whereYear('sale_date', now()->year)->whereMonth('sale_date', now()->month)
                                       ->where('status', 'completed')
                                       ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))->sum('total'),
            'month_purchases'   => Purchase::whereYear('purchase_date', now()->year)->whereMonth('purchase_date', now()->month)
                                            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))->sum('total'),
            'total_vehicles'    => VehicleModel::active()->count(),
            'total_spare_parts' => SparePart::active()->count(),
            'total_customers'   => Customer::active()->count(),
            'total_suppliers'   => Supplier::active()->count(),
        ];

        // Stock & low-stock — per warehouse or global
        if ($warehouseId) {
            $stats['low_stock_parts']    = DB::table('warehouse_spare_part_stock')
                ->where('warehouse_id', $warehouseId)->whereColumn('current_stock', '<=', 'reorder_level')->count();
            $stats['low_stock_vehicles'] = DB::table('warehouse_vehicle_stock')
                ->where('warehouse_id', $warehouseId)->whereColumn('current_stock', '<=', 'reorder_level')->count();
            $stats['inventory_value_parts'] = DB::table('warehouse_spare_part_stock as ws')
                ->join('spare_parts as sp', 'ws.spare_part_id', '=', 'sp.id')
                ->where('ws.warehouse_id', $warehouseId)
                ->sum(DB::raw('ws.current_stock * sp.buying_price'));
            $stats['inventory_value_vehicles'] = DB::table('warehouse_vehicle_stock as wv')
                ->join('vehicle_models as vm', 'wv.vehicle_model_id', '=', 'vm.id')
                ->where('wv.warehouse_id', $warehouseId)
                ->sum(DB::raw('wv.current_stock * vm.buying_price'));
        } else {
            $stats['low_stock_parts']    = SparePart::lowStock()->count();
            $stats['low_stock_vehicles'] = VehicleStock::whereColumn('current_stock', '<=', 'reorder_level')->count();
            $stats['inventory_value_parts'] = SparePart::selectRaw('SUM(current_stock * buying_price) as val')->value('val') ?? 0;
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
        if ($warehouseId) $profitQuery->where('s.warehouse_id', $warehouseId);
        $salesItems = $profitQuery->select('si.item_type', 'si.vehicle_model_id', 'si.spare_part_id', 'si.quantity', 'si.unit_price')->get();

        $profit = 0;
        foreach ($salesItems as $item) {
            $cost = $item->item_type === 'vehicle'
                ? (DB::table('vehicle_models')->where('id', $item->vehicle_model_id)->value('buying_price') ?? 0)
                : (DB::table('spare_parts')->where('id', $item->spare_part_id)->value('buying_price') ?? 0);
            $profit += ($item->unit_price - $cost) * $item->quantity;
        }
        $stats['month_profit'] = $profit;

        // ── Charts: Sales last 7 days ────────────────
        $salesChart = Sale::selectRaw('DATE(sale_date) as date, SUM(total) as total, COUNT(*) as count')
            ->where('status', 'completed')
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereBetween('sale_date', [now()->subDays(6)->toDateString(), $today])
            ->groupBy('date')->orderBy('date')->get()->keyBy('date');

        $chartLabels = [];
        $chartData   = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $chartLabels[] = now()->subDays($i)->format('M d');
            $chartData[]   = $salesChart[$d]->total ?? 0;
        }

        // ── Charts: Sales mix (vehicles vs parts) ──
        $vehicleSales = Sale::completed()
            ->when($warehouseId, fn($q) => $q->where('sales.warehouse_id', $warehouseId))
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.item_type', 'vehicle')
            ->whereYear('sales.sale_date', now()->year)->whereMonth('sales.sale_date', now()->month)
            ->sum('sale_items.total');

        $partSales = Sale::completed()
            ->when($warehouseId, fn($q) => $q->where('sales.warehouse_id', $warehouseId))
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.item_type', 'spare_part')
            ->whereYear('sales.sale_date', now()->year)->whereMonth('sales.sale_date', now()->month)
            ->sum('sale_items.total');

        // ── Recent sales ─────────────────────────────
        $recentSales = Sale::with('customer', 'user')
            ->where('status', 'completed')
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->latest()->limit(8)->get();

        // ── Low stock items ───────────────────────────
        if ($warehouseId) {
            $lowStockParts = SparePart::with('category', 'unit')
                ->whereHas('warehouses', fn($q) => $q->where('warehouse_id', $warehouseId)
                    ->whereColumn('warehouse_spare_part_stock.current_stock', '<=', 'warehouse_spare_part_stock.reorder_level'))
                ->active()->orderBy('current_stock')->limit(5)->get();

            $lowStockVehicles = collect(DB::table('warehouse_vehicle_stock as wv')
                ->join('vehicle_models as vm', 'wv.vehicle_model_id', '=', 'vm.id')
                ->join('vehicle_types as vt', 'vm.vehicle_type_id', '=', 'vt.id')
                ->where('wv.warehouse_id', $warehouseId)
                ->whereColumn('wv.current_stock', '<=', 'wv.reorder_level')
                ->orderBy('wv.current_stock')
                ->limit(5)
                ->select('wv.*', 'vm.brand', 'vm.model_name', 'vm.model_code', 'vt.name as type_name')
                ->get());
        } else {
            $lowStockParts = SparePart::with('category', 'unit')
                ->lowStock()->active()->orderBy('current_stock')->limit(5)->get();
            $lowStockVehicles = VehicleStock::with('vehicleModel.vehicleType')
                ->whereColumn('current_stock', '<=', 'reorder_level')->orderBy('current_stock')->limit(5)->get();
        }

        // ── Recent purchases ──────────────────────────
        $recentPurchases = Purchase::with('supplier')
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->latest()->limit(5)->get();

        return view('dashboard.index', compact(
            'stats', 'warehouses', 'warehouse', 'warehouseId',
            'chartLabels', 'chartData',
            'vehicleSales', 'partSales',
            'recentSales', 'lowStockParts', 'lowStockVehicles',
            'recentPurchases'
        ));
    }
}
