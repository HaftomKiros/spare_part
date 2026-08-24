<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SparePart;
use App\Models\VehicleModel;
use App\Models\VehicleStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /* ── Sales Report ─────────────────────────────── */
    public function sales(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo   = $request->date_to   ?? now()->format('Y-m-d');

        $query = Sale::completed()
            ->with('customer', 'user')
            ->whereBetween('sale_date', [$dateFrom, $dateTo]);

        $sales = $query->latest('sale_date')->paginate(25)->withQueryString();

        $summary = Sale::completed()
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->selectRaw('
                COUNT(*) as total_invoices,
                SUM(total) as gross_revenue,
                SUM(discount) as total_discounts,
                SUM(tax) as total_tax,
                SUM(paid_amount) as total_collected,
                SUM(balance) as total_outstanding
            ')->first();

        // Daily chart
        $daily = Sale::completed()
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->selectRaw('DATE(sale_date) as date, SUM(total) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('reports.sales', compact('sales', 'summary', 'daily', 'dateFrom', 'dateTo'));
    }

    /* ── Vehicles Report ──────────────────────────── */
    public function vehicles(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo   = $request->date_to   ?? now()->format('Y-m-d');

        $vehicles = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->join('vehicle_models as vm', 'si.vehicle_model_id', '=', 'vm.id')
            ->join('vehicle_types as vt', 'vm.vehicle_type_id', '=', 'vt.id')
            ->where('si.item_type', 'vehicle')
            ->where('s.status', 'completed')
            ->whereBetween('s.sale_date', [$dateFrom, $dateTo])
            ->selectRaw('
                vm.id, vm.brand, vm.model_name, vm.model_code, vm.selling_price, vm.buying_price,
                vt.name as type_name,
                SUM(si.quantity) as qty_sold,
                SUM(si.total) as revenue,
                SUM(si.quantity * vm.buying_price) as cost,
                SUM(si.total - (si.quantity * vm.buying_price)) as profit
            ')
            ->groupBy('vm.id', 'vm.brand', 'vm.model_name', 'vm.model_code',
                      'vm.selling_price', 'vm.buying_price', 'vt.name')
            ->orderByDesc('qty_sold')
            ->get();

        $totalRevenue = $vehicles->sum('revenue');
        $totalProfit  = $vehicles->sum('profit');
        $totalQty     = $vehicles->sum('qty_sold');

        return view('reports.vehicles', compact(
            'vehicles', 'totalRevenue', 'totalProfit', 'totalQty', 'dateFrom', 'dateTo'
        ));
    }

    /* ── Spare Parts Report ───────────────────────── */
    public function spareParts(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo   = $request->date_to   ?? now()->format('Y-m-d');

        $parts = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->join('spare_parts as sp', 'si.spare_part_id', '=', 'sp.id')
            ->join('part_categories as pc', 'sp.part_category_id', '=', 'pc.id')
            ->join('units as u', 'sp.unit_id', '=', 'u.id')
            ->where('si.item_type', 'spare_part')
            ->where('s.status', 'completed')
            ->whereBetween('s.sale_date', [$dateFrom, $dateTo])
            ->selectRaw('
                sp.id, sp.name, sp.part_number, sp.selling_price, sp.buying_price,
                pc.name as category_name,
                u.abbreviation as unit,
                SUM(si.quantity) as qty_sold,
                SUM(si.total) as revenue,
                SUM(si.quantity * sp.buying_price) as cost,
                SUM(si.total - (si.quantity * sp.buying_price)) as profit
            ')
            ->groupBy('sp.id', 'sp.name', 'sp.part_number', 'sp.selling_price',
                      'sp.buying_price', 'pc.name', 'u.abbreviation')
            ->orderByDesc('qty_sold')
            ->get();

        $totalRevenue = $parts->sum('revenue');
        $totalProfit  = $parts->sum('profit');
        $totalQty     = $parts->sum('qty_sold');

        return view('reports.spare-parts', compact(
            'parts', 'totalRevenue', 'totalProfit', 'totalQty', 'dateFrom', 'dateTo'
        ));
    }

    /* ── Stock Report ─────────────────────────────── */
    public function stock(Request $request)
    {
        $partsValue = SparePart::selectRaw('
            COUNT(*) as total_skus,
            SUM(current_stock) as total_qty,
            SUM(current_stock * buying_price) as buying_value,
            SUM(current_stock * selling_price) as selling_value
        ')->first();

        $vehiclesValue = VehicleStock::join('vehicle_models', 'vehicle_stocks.vehicle_model_id', '=', 'vehicle_models.id')
            ->selectRaw('
                COUNT(*) as total_models,
                SUM(vehicle_stocks.current_stock) as total_qty,
                SUM(vehicle_stocks.current_stock * vehicle_models.buying_price) as buying_value,
                SUM(vehicle_stocks.current_stock * vehicle_models.selling_price) as selling_value
            ')->first();

        // By category
        $byCat = DB::table('part_categories as pc')
            ->join('spare_parts as sp', 'pc.id', '=', 'sp.part_category_id')
            ->selectRaw('pc.name, COUNT(sp.id) as parts_count, SUM(sp.current_stock) as total_qty, SUM(sp.current_stock * sp.buying_price) as value')
            ->groupBy('pc.id', 'pc.name')
            ->orderByDesc('value')
            ->get();

        // By vehicle type
        $byType = DB::table('vehicle_types as vt')
            ->join('vehicle_models as vm', 'vt.id', '=', 'vm.vehicle_type_id')
            ->join('vehicle_stocks as vs', 'vm.id', '=', 'vs.vehicle_model_id')
            ->selectRaw('vt.name, COUNT(vm.id) as model_count, SUM(vs.current_stock) as total_qty, SUM(vs.current_stock * vm.buying_price) as value')
            ->groupBy('vt.id', 'vt.name')
            ->get();

        return view('reports.stock', compact('partsValue', 'vehiclesValue', 'byCat', 'byType'));
    }

    /* ── Low Stock Report ─────────────────────────── */
    public function lowStock(Request $request)
    {
        $lowParts = SparePart::with('category', 'unit')
            ->lowStock()
            ->active()
            ->orderBy('current_stock')
            ->get();

        $outParts = SparePart::with('category', 'unit')
            ->outOfStock()
            ->active()
            ->get();

        $lowVehicles = VehicleStock::with('vehicleModel.vehicleType')
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->orderBy('current_stock')
            ->get();

        $outVehicles = VehicleStock::with('vehicleModel.vehicleType')
            ->where('current_stock', '<=', 0)
            ->get();

        return view('reports.low-stock', compact('lowParts', 'outParts', 'lowVehicles', 'outVehicles'));
    }

    /* ── Purchases Report ─────────────────────────── */
    public function purchases(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo   = $request->date_to   ?? now()->format('Y-m-d');

        $purchases = Purchase::with('supplier', 'user')
            ->whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->latest('purchase_date')
            ->paginate(25)->withQueryString();

        $summary = Purchase::whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->selectRaw('
                COUNT(*) as total_orders,
                SUM(total) as total_amount,
                SUM(paid_amount) as total_paid,
                SUM(balance) as total_balance
            ')->first();

        // By supplier
        $bySupplier = Purchase::whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->selectRaw('suppliers.name, COUNT(*) as orders, SUM(purchases.total) as total')
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return view('reports.purchases', compact('purchases', 'summary', 'bySupplier', 'dateFrom', 'dateTo'));
    }

    /* ── Profit Report ────────────────────────────── */
    public function profit(Request $request)
    {
        $year  = $request->year  ?? now()->year;
        $month = $request->month ?? null;

        // Monthly profit for the selected year
        $monthly = DB::table('sales as s')
            ->join('sale_items as si', 's.id', '=', 'si.sale_id')
            ->where('s.status', 'completed')
            ->whereYear('s.sale_date', $year)
            ->when($month, fn($q) => $q->whereMonth('s.sale_date', $month))
            ->selectRaw('
                YEAR(s.sale_date) as year,
                MONTH(s.sale_date) as month,
                SUM(si.total) as revenue,
                SUM(
                    si.quantity * CASE
                        WHEN si.item_type = "vehicle"    THEN COALESCE(vm.buying_price, 0)
                        WHEN si.item_type = "spare_part" THEN COALESCE(sp.buying_price, 0)
                        ELSE 0
                    END
                ) as cost
            ')
            ->leftJoin('vehicle_models as vm', 'si.vehicle_model_id', '=', 'vm.id')
            ->leftJoin('spare_parts as sp', 'si.spare_part_id', '=', 'sp.id')
            ->groupByRaw('YEAR(s.sale_date), MONTH(s.sale_date)')
            ->orderByRaw('YEAR(s.sale_date), MONTH(s.sale_date)')
            ->get()
            ->map(function ($r) {
                $r->profit = $r->revenue - $r->cost;
                $r->margin = $r->revenue > 0 ? round(($r->profit / $r->revenue) * 100, 1) : 0;
                $r->month_name = \Carbon\Carbon::createFromDate($r->year, $r->month, 1)->format('M Y');
                return $r;
            });

        $totalRevenue = $monthly->sum('revenue');
        $totalCost    = $monthly->sum('cost');
        $totalProfit  = $monthly->sum('profit');
        $avgMargin    = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0;

        // Top profitable spare parts
        $topParts = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->join('spare_parts as sp', 'si.spare_part_id', '=', 'sp.id')
            ->where('s.status', 'completed')
            ->where('si.item_type', 'spare_part')
            ->whereYear('s.sale_date', $year)
            ->when($month, fn($q) => $q->whereMonth('s.sale_date', $month))
            ->selectRaw('sp.name, sp.part_number, SUM(si.quantity) as qty, SUM(si.total) as revenue, SUM(si.quantity * sp.buying_price) as cost, SUM(si.total - (si.quantity * sp.buying_price)) as profit')
            ->groupBy('sp.id', 'sp.name', 'sp.part_number')
            ->orderByDesc('profit')
            ->limit(10)
            ->get();

        // Top profitable vehicles
        $topVehicles = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->join('vehicle_models as vm', 'si.vehicle_model_id', '=', 'vm.id')
            ->where('s.status', 'completed')
            ->where('si.item_type', 'vehicle')
            ->whereYear('s.sale_date', $year)
            ->when($month, fn($q) => $q->whereMonth('s.sale_date', $month))
            ->selectRaw('vm.brand, vm.model_name, SUM(si.quantity) as qty, SUM(si.total) as revenue, SUM(si.quantity * vm.buying_price) as cost, SUM(si.total - (si.quantity * vm.buying_price)) as profit')
            ->groupBy('vm.id', 'vm.brand', 'vm.model_name')
            ->orderByDesc('profit')
            ->limit(10)
            ->get();

        $years = range(now()->year, max(2020, now()->year - 4));

        return view('reports.profit', compact(
            'monthly', 'totalRevenue', 'totalCost', 'totalProfit', 'avgMargin',
            'topParts', 'topVehicles', 'year', 'month', 'years'
        ));
    }
}
