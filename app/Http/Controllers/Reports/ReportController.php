<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SparePart;
use App\Models\VehicleModel;
use App\Models\VehicleStock;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /* ── Sales Report ─────────────────────────────── */
    public function sales(Request $request)
    {
        $dateFrom      = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo        = $request->date_to   ?? now()->format('Y-m-d');
        $warehouseId   = $request->warehouse_id ? (int) $request->warehouse_id : null;
        $user          = auth()->user();
        $warehouses    = $user->accessibleWarehouses()->get();
        $accessibleIds = $user->accessibleWarehouseIds();
        if ($warehouseId && ! in_array($warehouseId, $accessibleIds)) { $warehouseId = null; }

        // Sales reports: warehouse + user_id for non-admins
        $scope = fn($q) => $q
            ->whereIn('warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q2) => $q2->where('user_id', $user->id))
            ->when($warehouseId, fn($q2) => $q2->where('warehouse_id', $warehouseId));

        $query   = Sale::completed()->with('customer', 'user')->whereBetween('sale_date', [$dateFrom, $dateTo]);
        $scope($query);
        $sales   = $query->latest('sale_date')->paginate(25)->withQueryString();

        $summary = Sale::completed()->whereBetween('sale_date', [$dateFrom, $dateTo]);
        $scope($summary);
        $summary = $summary->selectRaw('
            COUNT(*) as total_invoices,
            SUM(total) as gross_revenue,
            SUM(discount) as total_discounts,
            SUM(tax) as total_tax,
            SUM(paid_amount) as total_collected,
            SUM(balance) as total_outstanding
        ')->first();

        $daily = Sale::completed()->whereBetween('sale_date', [$dateFrom, $dateTo]);
        $scope($daily);
        $daily = $daily->selectRaw('DATE(sale_date) as date, SUM(total) as total, COUNT(*) as count')
            ->groupBy('date')->orderBy('date')->get();

        // ── Net Sales Profit per day (selling price − COGS) ──────────────
        $profitRows = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->leftJoin('vehicle_models as vm', 'si.vehicle_model_id', '=', 'vm.id')
            ->where('s.status', 'completed')
            ->whereBetween('s.sale_date', [$dateFrom, $dateTo])
            ->whereIn('s.warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('s.user_id', $user->id))
            ->when($warehouseId, fn($q) => $q->where('s.warehouse_id', $warehouseId))
            ->selectRaw("
                DATE(s.sale_date) as date,
                SUM(
                    si.quantity * (
                        si.unit_price - COALESCE(
                            CASE
                                WHEN si.item_type = 'vehicle'    THEN vm.buying_price
                                WHEN si.item_type = 'spare_part' THEN (
                                    SELECT pi2.unit_price
                                    FROM purchase_items pi2
                                    JOIN purchases p2 ON pi2.purchase_id = p2.id
                                    WHERE pi2.spare_part_id = si.spare_part_id
                                    ORDER BY p2.purchase_date DESC
                                    LIMIT 1
                                )
                                ELSE 0
                            END, 0
                        )
                    )
                ) as profit
            ")
            ->groupByRaw('DATE(s.sale_date)')
            ->orderByRaw('DATE(s.sale_date)')
            ->get()
            ->keyBy('date');

        // Attach profit to each daily row
        $daily = $daily->map(function ($row) use ($profitRows) {
            $row->profit = $profitRows[$row->date]->profit ?? 0;
            return $row;
        });

        $totalProfit = $daily->sum('profit');

        return view('reports.sales', compact('sales', 'summary', 'daily', 'totalProfit', 'dateFrom', 'dateTo', 'warehouses', 'warehouseId'));
    }

    /* ── Vehicles Report ──────────────────────────── */
    public function vehicles(Request $request)
    {
        $dateFrom      = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo        = $request->date_to   ?? now()->format('Y-m-d');
        $warehouseId   = $request->warehouse_id ? (int) $request->warehouse_id : null;
        $user          = auth()->user();
        $warehouses    = $user->accessibleWarehouses()->get();
        $accessibleIds = $user->accessibleWarehouseIds();
        if ($warehouseId && ! in_array($warehouseId, $accessibleIds)) { $warehouseId = null; }

        $vehicles = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->join('vehicle_models as vm', 'si.vehicle_model_id', '=', 'vm.id')
            ->join('vehicle_types as vt', 'vm.vehicle_type_id', '=', 'vt.id')
            ->where('si.item_type', 'vehicle')
            ->where('s.status', 'completed')
            ->whereBetween('s.sale_date', [$dateFrom, $dateTo])
            ->whereIn('s.warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('s.user_id', $user->id))
            ->when($warehouseId, fn($q) => $q->where('s.warehouse_id', $warehouseId))
            ->selectRaw('
                vm.id, vm.brand, vm.model_name, vm.model_code, vm.buying_price,
                vt.name as type_name,
                SUM(si.quantity) as qty_sold,
                SUM(si.total) as revenue,
                SUM(si.quantity * vm.buying_price) as cost,
                SUM(si.total - (si.quantity * vm.buying_price)) as profit
            ')
            ->groupBy('vm.id', 'vm.brand', 'vm.model_name', 'vm.model_code',
                      'vm.buying_price', 'vt.name')
            ->orderByDesc('qty_sold')->get();

        $totalRevenue = $vehicles->sum('revenue');
        $totalProfit  = $vehicles->sum('profit');
        $totalQty     = $vehicles->sum('qty_sold');

        return view('reports.vehicles', compact(
            'vehicles', 'totalRevenue', 'totalProfit', 'totalQty',
            'dateFrom', 'dateTo', 'warehouses', 'warehouseId'
        ));
    }

    /* ── Spare Parts Report ───────────────────────── */
    public function spareParts(Request $request)
    {
        $dateFrom      = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo        = $request->date_to   ?? now()->format('Y-m-d');
        $warehouseId   = $request->warehouse_id ? (int) $request->warehouse_id : null;
        $user          = auth()->user();
        $warehouses    = $user->accessibleWarehouses()->get();
        $accessibleIds = $user->accessibleWarehouseIds();
        if ($warehouseId && ! in_array($warehouseId, $accessibleIds)) { $warehouseId = null; }

        $parts = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->join('spare_parts as sp', 'si.spare_part_id', '=', 'sp.id')
            ->join('part_categories as pc', 'sp.part_category_id', '=', 'pc.id')
            ->join('units as u', 'sp.unit_id', '=', 'u.id')
            ->where('si.item_type', 'spare_part')
            ->where('s.status', 'completed')
            ->whereBetween('s.sale_date', [$dateFrom, $dateTo])
            ->whereIn('s.warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('s.user_id', $user->id))
            ->when($warehouseId, fn($q) => $q->where('s.warehouse_id', $warehouseId))
            ->selectRaw('
                sp.id, sp.name, sp.part_number, sp.buying_price,
                pc.name as category_name,
                u.abbreviation as unit,
                SUM(si.quantity) as qty_sold,
                SUM(si.total) as revenue,
                SUM(si.quantity * sp.buying_price) as cost,
                SUM(si.total - (si.quantity * sp.buying_price)) as profit
            ')
            ->groupBy('sp.id', 'sp.name', 'sp.part_number', 'sp.buying_price', 'pc.name', 'u.abbreviation')
            ->orderByDesc('qty_sold')->get();

        $totalRevenue = $parts->sum('revenue');
        $totalProfit  = $parts->sum('profit');
        $totalQty     = $parts->sum('qty_sold');

        return view('reports.spare-parts', compact(
            'parts', 'totalRevenue', 'totalProfit', 'totalQty',
            'dateFrom', 'dateTo', 'warehouses', 'warehouseId'
        ));
    }

    /* ── Stock Report ─────────────────────────────── */
    public function stock(Request $request)
    {
        $user          = auth()->user();
        $warehouses    = $user->accessibleWarehouses()->get();
        $accessibleIds = $user->accessibleWarehouseIds();
        $warehouseId   = $request->warehouse_id ? (int) $request->warehouse_id : null;
        if ($warehouseId && ! in_array($warehouseId, $accessibleIds)) { $warehouseId = null; }

        // Non-admins must always see a specific warehouse — default to first accessible
        if (! $warehouseId && ! $user->isAdmin()) {
            $warehouseId = $accessibleIds[0] ?? null;
        }

        if ($warehouseId) {
            // Per-warehouse stock from pivot tables
            $partsValue = DB::table('warehouse_spare_part_stock as ws')
                ->join('spare_parts as sp', 'ws.spare_part_id', '=', 'sp.id')
                ->where('ws.warehouse_id', $warehouseId)
                ->selectRaw('COUNT(*) as total_skus, SUM(ws.current_stock) as total_qty')
                ->first();
            $partsValue->buying_value  = \App\Services\StockService::partsStockValue([$warehouseId]);
            $partsValue->selling_value = 0;

            $vehiclesValue = DB::table('warehouse_vehicle_stock as wv')
                ->join('vehicle_models as vm', 'wv.vehicle_model_id', '=', 'vm.id')
                ->where('wv.warehouse_id', $warehouseId)
                ->selectRaw('COUNT(*) as total_models, SUM(wv.current_stock) as total_qty')
                ->first();
            $vehiclesValue->buying_value  = \App\Services\StockService::vehiclesStockValue([$warehouseId]);
            $vehiclesValue->selling_value = 0;

            $byCat = DB::table('warehouse_spare_part_stock as ws')
                ->join('spare_parts as sp', 'ws.spare_part_id', '=', 'sp.id')
                ->join('part_categories as pc', 'sp.part_category_id', '=', 'pc.id')
                ->where('ws.warehouse_id', $warehouseId)
                ->selectRaw('pc.name, pc.id as cat_id, COUNT(sp.id) as parts_count, SUM(ws.current_stock) as total_qty')
                ->groupBy('pc.id', 'pc.name')->orderByDesc('total_qty')
                ->get()
                ->map(function ($row) use ($warehouseId) {
                    $partIds = DB::table('warehouse_spare_part_stock as ws')
                        ->join('spare_parts as sp', 'ws.spare_part_id', '=', 'sp.id')
                        ->where('ws.warehouse_id', $warehouseId)
                        ->where('sp.part_category_id', $row->cat_id)
                        ->pluck('ws.spare_part_id')->toArray();
                    $valueMap = \App\Services\StockService::partsStockValueMap($partIds, [$warehouseId]);
                    $row->value = array_sum($valueMap);
                    return $row;
                });

            $byType = DB::table('warehouse_vehicle_stock as wv')
                ->join('vehicle_models as vm', 'wv.vehicle_model_id', '=', 'vm.id')
                ->join('vehicle_types as vt', 'vm.vehicle_type_id', '=', 'vt.id')
                ->where('wv.warehouse_id', $warehouseId)
                ->selectRaw('vt.name, vt.id as type_id, COUNT(vm.id) as model_count, SUM(wv.current_stock) as total_qty')
                ->groupBy('vt.id', 'vt.name')
                ->get()
                ->map(function ($row) use ($warehouseId) {
                    $vmIds    = DB::table('warehouse_vehicle_stock as wv')
                        ->join('vehicle_models as vm', 'wv.vehicle_model_id', '=', 'vm.id')
                        ->where('wv.warehouse_id', $warehouseId)
                        ->where('vm.vehicle_type_id', $row->type_id)
                        ->pluck('wv.vehicle_model_id')->toArray();
                    $valueMap = \App\Services\StockService::vehiclesStockValueMap($vmIds, [$warehouseId]);
                    $row->value = array_sum($valueMap);
                    return $row;
                });
        } else {
            $partsValue = SparePart::selectRaw('COUNT(*) as total_skus, SUM(current_stock) as total_qty')->first();
            $partsValue->buying_value  = \App\Services\StockService::partsStockValue();
            $partsValue->selling_value = 0;

            $vehiclesValue = DB::table('warehouse_vehicle_stock as wv')
                ->join('vehicle_models as vm', 'wv.vehicle_model_id', '=', 'vm.id')
                ->selectRaw('COUNT(*) as total_models, SUM(wv.current_stock) as total_qty')
                ->first();
            $vehiclesValue->buying_value  = \App\Services\StockService::vehiclesStockValue();
            $vehiclesValue->selling_value = 0;

            $byCat = DB::table('part_categories as pc')
                ->join('spare_parts as sp', 'pc.id', '=', 'sp.part_category_id')
                ->selectRaw('pc.name, pc.id as cat_id, COUNT(sp.id) as parts_count, SUM(sp.current_stock) as total_qty')
                ->groupBy('pc.id', 'pc.name')->orderByDesc('total_qty')
                ->get()
                ->map(function ($row) {
                    $partIds  = DB::table('spare_parts')->where('part_category_id', $row->cat_id)->pluck('id')->toArray();
                    $valueMap = \App\Services\StockService::partsStockValueMap($partIds);
                    $row->value = array_sum($valueMap);
                    return $row;
                });

            $byType = DB::table('vehicle_types as vt')
                ->join('vehicle_models as vm', 'vt.id', '=', 'vm.vehicle_type_id')
                ->join('vehicle_stocks as vs', 'vm.id', '=', 'vs.vehicle_model_id')
                ->selectRaw('vt.name, vt.id as type_id, COUNT(vm.id) as model_count, SUM(vs.current_stock) as total_qty')
                ->groupBy('vt.id', 'vt.name')
                ->get()
                ->map(function ($row) {
                    $vmIds    = DB::table('vehicle_stocks as vs')
                        ->join('vehicle_models as vm', 'vs.vehicle_model_id', '=', 'vm.id')
                        ->where('vm.vehicle_type_id', $row->type_id)
                        ->pluck('vs.vehicle_model_id')->toArray();
                    $valueMap = \App\Services\StockService::vehiclesStockValueMap($vmIds);
                    $row->value = array_sum($valueMap);
                    return $row;
                });
        }

        return view('reports.stock', compact('partsValue', 'vehiclesValue', 'byCat', 'byType', 'warehouses', 'warehouseId'));
    }

    /* ── Low Stock Report ─────────────────────────── */
    public function lowStock(Request $request)
    {
        $user          = auth()->user();
        $warehouses    = $user->accessibleWarehouses()->get();
        $accessibleIds = $user->accessibleWarehouseIds();
        $warehouseId   = $request->warehouse_id ? (int) $request->warehouse_id : null;
        if ($warehouseId && ! in_array($warehouseId, $accessibleIds)) { $warehouseId = null; }

        // Non-admins always default to their first accessible warehouse
        if (! $warehouseId && ! $user->isAdmin()) {
            $warehouseId = $accessibleIds[0] ?? null;
        }

        if ($warehouseId) {
            $lowParts = DB::table('warehouse_spare_part_stock as ws')
                ->join('spare_parts as sp', 'ws.spare_part_id', '=', 'sp.id')
                ->join('part_categories as pc', 'sp.part_category_id', '=', 'pc.id')
                ->join('units as u', 'sp.unit_id', '=', 'u.id')
                ->where('ws.warehouse_id', $warehouseId)
                ->where('ws.current_stock', '>', 0)
                ->whereColumn('ws.current_stock', '<=', 'ws.reorder_level')
                ->where('sp.status', 'active')
                ->selectRaw('sp.id, sp.name, sp.part_number, pc.name as category, u.abbreviation as unit_abbr, ws.current_stock, ws.reorder_level')
                ->orderBy('ws.current_stock')->get();

            $outParts = DB::table('warehouse_spare_part_stock as ws')
                ->join('spare_parts as sp', 'ws.spare_part_id', '=', 'sp.id')
                ->join('part_categories as pc', 'sp.part_category_id', '=', 'pc.id')
                ->join('units as u', 'sp.unit_id', '=', 'u.id')
                ->where('ws.warehouse_id', $warehouseId)
                ->where('ws.current_stock', '<=', 0)
                ->where('sp.status', 'active')
                ->selectRaw('sp.id, sp.name, sp.part_number, pc.name as category, u.abbreviation as unit_abbr, ws.current_stock, ws.reorder_level')
                ->get();

            $lowVehicles = DB::table('warehouse_vehicle_stock as wv')
                ->join('vehicle_models as vm', 'wv.vehicle_model_id', '=', 'vm.id')
                ->join('vehicle_types as vt', 'vm.vehicle_type_id', '=', 'vt.id')
                ->where('wv.warehouse_id', $warehouseId)
                ->where('wv.current_stock', '>', 0)
                ->whereColumn('wv.current_stock', '<=', 'wv.reorder_level')
                ->selectRaw('vm.id, vm.brand, vm.model_name, vm.model_code, vt.name as type_name, wv.current_stock, wv.reorder_level')
                ->orderBy('wv.current_stock')->get();

            $outVehicles = DB::table('warehouse_vehicle_stock as wv')
                ->join('vehicle_models as vm', 'wv.vehicle_model_id', '=', 'vm.id')
                ->join('vehicle_types as vt', 'vm.vehicle_type_id', '=', 'vt.id')
                ->where('wv.warehouse_id', $warehouseId)
                ->where('wv.current_stock', '<=', 0)
                ->selectRaw('vm.id, vm.brand, vm.model_name, vm.model_code, vt.name as type_name, wv.current_stock, wv.reorder_level')
                ->get();

            return view('reports.low-stock', compact('lowParts', 'outParts', 'lowVehicles', 'outVehicles', 'warehouses', 'warehouseId'));
        }

        // All accessible warehouses — query the same warehouse stock tables
        // the notification badge uses so counts always match what is displayed.
        $lowParts = DB::table('warehouse_spare_part_stock as ws')
            ->join('spare_parts as sp', 'ws.spare_part_id', '=', 'sp.id')
            ->join('part_categories as pc', 'sp.part_category_id', '=', 'pc.id')
            ->join('units as u', 'sp.unit_id', '=', 'u.id')
            ->whereIn('ws.warehouse_id', $accessibleIds)
            ->where('ws.current_stock', '>', 0)
            ->whereColumn('ws.current_stock', '<=', 'ws.reorder_level')
            ->where('sp.status', 'active')
            ->selectRaw('sp.id, sp.name, sp.part_number, pc.name as category, u.abbreviation as unit_abbr,
                         MAX(ws.current_stock) as current_stock, MAX(ws.reorder_level) as reorder_level')
            ->groupBy('sp.id', 'sp.name', 'sp.part_number', 'pc.name', 'u.abbreviation')
            ->orderBy('current_stock')
            ->get();

        $outParts = DB::table('warehouse_spare_part_stock as ws')
            ->join('spare_parts as sp', 'ws.spare_part_id', '=', 'sp.id')
            ->join('part_categories as pc', 'sp.part_category_id', '=', 'pc.id')
            ->join('units as u', 'sp.unit_id', '=', 'u.id')
            ->whereIn('ws.warehouse_id', $accessibleIds)
            ->where('ws.current_stock', '<=', 0)
            ->where('sp.status', 'active')
            ->selectRaw('sp.id, sp.name, sp.part_number, pc.name as category, u.abbreviation as unit_abbr,
                         ws.current_stock, ws.reorder_level')
            ->get();

        $lowVehicles = DB::table('warehouse_vehicle_stock as wv')
            ->join('vehicle_models as vm', 'wv.vehicle_model_id', '=', 'vm.id')
            ->join('vehicle_types as vt', 'vm.vehicle_type_id', '=', 'vt.id')
            ->whereIn('wv.warehouse_id', $accessibleIds)
            ->where('wv.current_stock', '>', 0)
            ->whereColumn('wv.current_stock', '<=', 'wv.reorder_level')
            ->selectRaw('vm.id, vm.brand, vm.model_name, vm.model_code, vt.name as type_name,
                         wv.current_stock, wv.reorder_level')
            ->orderBy('wv.current_stock')
            ->get();

        $outVehicles = DB::table('warehouse_vehicle_stock as wv')
            ->join('vehicle_models as vm', 'wv.vehicle_model_id', '=', 'vm.id')
            ->join('vehicle_types as vt', 'vm.vehicle_type_id', '=', 'vt.id')
            ->whereIn('wv.warehouse_id', $accessibleIds)
            ->where('wv.current_stock', '<=', 0)
            ->selectRaw('vm.id, vm.brand, vm.model_name, vm.model_code, vt.name as type_name,
                         wv.current_stock, wv.reorder_level')
            ->get();

        return view('reports.low-stock', compact('lowParts', 'outParts', 'lowVehicles', 'outVehicles', 'warehouses', 'warehouseId'));
    }

    /* ── Purchases Report ─────────────────────────── */
    public function purchases(Request $request)
    {
        $dateFrom      = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo        = $request->date_to   ?? now()->format('Y-m-d');
        $warehouseId   = $request->warehouse_id ? (int) $request->warehouse_id : null;
        $user          = auth()->user();
        $warehouses    = $user->accessibleWarehouses()->get();
        $accessibleIds = $user->accessibleWarehouseIds();
        if ($warehouseId && ! in_array($warehouseId, $accessibleIds)) { $warehouseId = null; }

        $purchases = Purchase::with('supplier', 'user')
            ->whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->whereIn('warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('user_id', $user->id))
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->latest('purchase_date')->paginate(25)->withQueryString();

        $summary = Purchase::whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->whereIn('warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('user_id', $user->id))
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->selectRaw('
                COUNT(*) as total_orders,
                SUM(total) as total_amount,
                SUM(paid_amount) as total_paid,
                SUM(balance) as total_balance
            ')->first();

        $bySupplier = Purchase::whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->whereIn('purchases.warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('purchases.user_id', $user->id))
            ->when($warehouseId, fn($q) => $q->where('purchases.warehouse_id', $warehouseId))
            ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->selectRaw('suppliers.name, COUNT(*) as orders, SUM(purchases.total) as total')
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total')->limit(10)->get();

        return view('reports.purchases', compact('purchases', 'summary', 'bySupplier', 'dateFrom', 'dateTo', 'warehouses', 'warehouseId'));
    }

    /* ── Profit Report ────────────────────────────── */
    public function profit(Request $request)
    {
        $year          = $request->year  ?? now()->year;
        $month         = $request->month ?? null;
        $warehouseId   = $request->warehouse_id ? (int) $request->warehouse_id : null;
        $user          = auth()->user();
        $warehouses    = $user->accessibleWarehouses()->get();
        $accessibleIds = $user->accessibleWarehouseIds();
        if ($warehouseId && ! in_array($warehouseId, $accessibleIds)) { $warehouseId = null; }

        $monthly = DB::table('sales as s')
            ->join('sale_items as si', 's.id', '=', 'si.sale_id')
            ->where('s.status', 'completed')
            ->whereYear('s.sale_date', $year)
            ->whereIn('s.warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('s.user_id', $user->id))
            ->when($month, fn($q) => $q->whereMonth('s.sale_date', $month))
            ->when($warehouseId, fn($q) => $q->where('s.warehouse_id', $warehouseId))
            ->selectRaw('
                YEAR(s.sale_date) as year,
                MONTH(s.sale_date) as month,
                SUM(si.total) as revenue,
                SUM(
                    si.quantity * CASE
                        WHEN si.item_type = "vehicle"    THEN COALESCE(vm.buying_price, 0)
                        WHEN si.item_type = "spare_part" THEN COALESCE(si.unit_price * 0, 0)
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
                $r->profit     = $r->revenue - $r->cost;
                $r->margin     = $r->revenue > 0 ? round(($r->profit / $r->revenue) * 100, 1) : 0;
                $r->month_name = \Carbon\Carbon::createFromDate($r->year, $r->month, 1)->format('M Y');
                return $r;
            });

        $totalRevenue = $monthly->sum('revenue');
        $totalCost    = $monthly->sum('cost');
        $totalProfit  = $monthly->sum('profit');
        $avgMargin    = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0;

        // Total expenses for the period (warehouse-scoped)
        $expensesQuery = DB::table('expenses')
            ->whereYear('expense_date', $year)
            ->where(function ($q) use ($accessibleIds) {
                $q->whereIn('warehouse_id', $accessibleIds)->orWhereNull('warehouse_id');
            })
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('user_id', $user->id))
            ->when($month, fn($q) => $q->whereMonth('expense_date', $month))
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId));

        $totalExpenses = $expensesQuery->sum('amount');
        $netProfit     = $totalProfit - $totalExpenses;

        $topParts = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->join('spare_parts as sp', 'si.spare_part_id', '=', 'sp.id')
            ->where('s.status', 'completed')->where('si.item_type', 'spare_part')
            ->whereYear('s.sale_date', $year)
            ->whereIn('s.warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('s.user_id', $user->id))
            ->when($month, fn($q) => $q->whereMonth('s.sale_date', $month))
            ->when($warehouseId, fn($q) => $q->where('s.warehouse_id', $warehouseId))
            ->selectRaw('sp.name, sp.part_number, SUM(si.quantity) as qty, SUM(si.total) as revenue, 0 as cost, SUM(si.total) as profit')
            ->groupBy('sp.id', 'sp.name', 'sp.part_number')
            ->orderByDesc('profit')->limit(10)->get();

        $topVehicles = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->join('vehicle_models as vm', 'si.vehicle_model_id', '=', 'vm.id')
            ->where('s.status', 'completed')->where('si.item_type', 'vehicle')
            ->whereYear('s.sale_date', $year)
            ->whereIn('s.warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('s.user_id', $user->id))
            ->when($month, fn($q) => $q->whereMonth('s.sale_date', $month))
            ->when($warehouseId, fn($q) => $q->where('s.warehouse_id', $warehouseId))
            ->selectRaw('vm.brand, vm.model_name, SUM(si.quantity) as qty, SUM(si.total) as revenue, SUM(si.quantity * vm.buying_price) as cost, SUM(si.total - (si.quantity * vm.buying_price)) as profit')
            ->groupBy('vm.id', 'vm.brand', 'vm.model_name')
            ->orderByDesc('profit')->limit(10)->get();

        $years = range(now()->year, max(2020, now()->year - 4));

        return view('reports.profit', compact(
            'monthly', 'totalRevenue', 'totalCost', 'totalProfit', 'avgMargin',
            'topParts', 'topVehicles', 'year', 'month', 'years',
            'warehouses', 'warehouseId',
            'totalExpenses', 'netProfit'
        ));
    }

    /* ── Expenses Report ──────────────────────────── */
    public function expenses(Request $request)
    {
        $dateFrom      = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo        = $request->date_to   ?? now()->format('Y-m-d');
        $warehouseId   = $request->warehouse_id ? (int) $request->warehouse_id : null;
        $categoryId    = $request->category_id  ? (int) $request->category_id  : null;
        $user          = auth()->user();
        $warehouses    = $user->accessibleWarehouses()->get();
        $accessibleIds = $user->accessibleWarehouseIds();
        $categories    = ExpenseCategory::active()->orderBy('name')->get();

        if ($warehouseId && ! in_array($warehouseId, $accessibleIds)) { $warehouseId = null; }

        $query = DB::table('expenses as e')
            ->join('expense_categories as ec', 'e.expense_category_id', '=', 'ec.id')
            ->join('users as u', 'e.user_id', '=', 'u.id')
            ->leftJoin('warehouses as w', 'e.warehouse_id', '=', 'w.id')
            ->whereBetween('e.expense_date', [$dateFrom, $dateTo])
            ->where(function ($q) use ($accessibleIds) {
                $q->whereIn('e.warehouse_id', $accessibleIds)->orWhereNull('e.warehouse_id');
            })
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('e.user_id', $user->id))
            ->when($warehouseId, fn($q) => $q->where('e.warehouse_id', $warehouseId))
            ->when($categoryId,  fn($q) => $q->where('e.expense_category_id', $categoryId))
            ->select(
                'e.id', 'e.expense_number', 'e.title', 'e.amount',
                'e.expense_date', 'e.payment_method', 'e.reference_number',
                'ec.name as category_name',
                'u.name as user_name',
                'w.name as warehouse_name'
            )
            ->orderByDesc('e.expense_date');

        $expenses = $query->paginate(25)->withQueryString();

        // Summary by category
        $byCategory = DB::table('expenses as e')
            ->join('expense_categories as ec', 'e.expense_category_id', '=', 'ec.id')
            ->whereBetween('e.expense_date', [$dateFrom, $dateTo])
            ->where(function ($q) use ($accessibleIds) {
                $q->whereIn('e.warehouse_id', $accessibleIds)->orWhereNull('e.warehouse_id');
            })
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('e.user_id', $user->id))
            ->when($warehouseId, fn($q) => $q->where('e.warehouse_id', $warehouseId))
            ->selectRaw('ec.name as category, COUNT(*) as count, SUM(e.amount) as total')
            ->groupBy('ec.id', 'ec.name')
            ->orderByDesc('total')
            ->get();

        $totalAmount = $byCategory->sum('total');

        return view('reports.expenses', compact(
            'expenses', 'byCategory', 'totalAmount',
            'dateFrom', 'dateTo', 'warehouses', 'warehouseId',
            'categories', 'categoryId'
        ));
    }
}
