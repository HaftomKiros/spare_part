<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SparePart;
use App\Models\Supplier;
use App\Models\VehicleModel;
use App\Models\VehicleStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today     = now()->toDateString();
        $thisMonth = now()->format('Y-m');
        $lastMonth = now()->subMonth()->format('Y-m');

        // ── Stat Cards ────────────────────────────────
        $stats = [
            'today_sales'       => Sale::whereDate('sale_date', $today)->where('status', 'completed')->sum('total'),
            'today_sales_count' => Sale::whereDate('sale_date', $today)->where('status', 'completed')->count(),
            'month_sales'       => Sale::whereYear('sale_date', now()->year)
                                       ->whereMonth('sale_date', now()->month)
                                       ->where('status', 'completed')->sum('total'),
            'month_purchases'   => Purchase::whereYear('purchase_date', now()->year)
                                            ->whereMonth('purchase_date', now()->month)
                                            ->sum('total'),
            'total_vehicles'    => VehicleModel::active()->count(),
            'total_spare_parts' => SparePart::active()->count(),
            'total_customers'   => Customer::active()->count(),
            'total_suppliers'   => Supplier::active()->count(),
            'low_stock_parts'   => SparePart::lowStock()->count(),
            'low_stock_vehicles'=> VehicleStock::whereColumn('current_stock', '<=', 'reorder_level')->count(),
        ];

        // Total inventory value
        $stats['inventory_value_parts']    = SparePart::selectRaw('SUM(current_stock * buying_price) as val')->value('val') ?? 0;
        $stats['inventory_value_vehicles'] = DB::table('vehicle_stocks as vs')
            ->join('vehicle_models as vm', 'vs.vehicle_model_id', '=', 'vm.id')
            ->selectRaw('SUM(vs.current_stock * vm.buying_price) as val')
            ->value('val') ?? 0;
        $stats['total_inventory_value'] = $stats['inventory_value_parts'] + $stats['inventory_value_vehicles'];

        // Profit this month
        $salesItems = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->where('s.status', 'completed')
            ->whereYear('s.sale_date', now()->year)
            ->whereMonth('s.sale_date', now()->month)
            ->select('si.item_type', 'si.vehicle_model_id', 'si.spare_part_id', 'si.quantity', 'si.unit_price')
            ->get();

        $profit = 0;
        foreach ($salesItems as $item) {
            $cost = 0;
            if ($item->item_type === 'vehicle') {
                $cost = DB::table('vehicle_models')->where('id', $item->vehicle_model_id)->value('buying_price') ?? 0;
            } else {
                $cost = DB::table('spare_parts')->where('id', $item->spare_part_id)->value('buying_price') ?? 0;
            }
            $profit += ($item->unit_price - $cost) * $item->quantity;
        }
        $stats['month_profit'] = $profit;

        // ── Charts: Sales last 7 days ────────────────
        $salesChart = Sale::selectRaw('DATE(sale_date) as date, SUM(total) as total, COUNT(*) as count')
            ->where('status', 'completed')
            ->whereBetween('sale_date', [now()->subDays(6)->toDateString(), $today])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $chartLabels = [];
        $chartData   = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $chartLabels[] = now()->subDays($i)->format('M d');
            $chartData[]   = $salesChart[$d]->total ?? 0;
        }

        // ── Charts: Sales by category (parts vs vehicles) ──
        $vehicleSales = Sale::completed()
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.item_type', 'vehicle')
            ->whereYear('sales.sale_date', now()->year)
            ->whereMonth('sales.sale_date', now()->month)
            ->sum('sale_items.total');

        $partSales = Sale::completed()
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.item_type', 'spare_part')
            ->whereYear('sales.sale_date', now()->year)
            ->whereMonth('sales.sale_date', now()->month)
            ->sum('sale_items.total');

        // ── Recent sales ─────────────────────────────
        $recentSales = Sale::with('customer', 'user')
            ->where('status', 'completed')
            ->latest()
            ->limit(8)
            ->get();

        // ── Low stock items ───────────────────────────
        $lowStockParts = SparePart::with('category', 'unit')
            ->lowStock()
            ->active()
            ->orderBy('current_stock')
            ->limit(5)
            ->get();

        $lowStockVehicles = VehicleStock::with('vehicleModel.vehicleType')
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->orderBy('current_stock')
            ->limit(5)
            ->get();

        // ── Recent purchases ──────────────────────────
        $recentPurchases = Purchase::with('supplier')
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard.index', compact(
            'stats',
            'chartLabels', 'chartData',
            'vehicleSales', 'partSales',
            'recentSales',
            'lowStockParts', 'lowStockVehicles',
            'recentPurchases'
        ));
    }
}
