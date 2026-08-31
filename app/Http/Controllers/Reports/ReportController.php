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
        $itemType      = in_array($request->item_type, ['spare_part', 'vehicle']) ? $request->item_type : null;
        $user          = auth()->user();
        $warehouses    = $user->accessibleWarehouses()->get();
        $accessibleIds = $user->accessibleWarehouseIds();
        if ($warehouseId && ! in_array($warehouseId, $accessibleIds)) { $warehouseId = null; }

        // Sales reports: warehouse + user_id for non-admins
        $scope = fn($q) => $q
            ->whereIn('warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q2) => $q2->where('user_id', $user->id))
            ->when($warehouseId, fn($q2) => $q2->where('warehouse_id', $warehouseId));

        // Item type scope — only include sales that have at least one item of the selected type
        $typeScope = fn($q) => $q->when($itemType, fn($q2) => $q2->whereHas('items', fn($q3) => $q3->where('item_type', $itemType)));

        $query = Sale::completed()->with('customer', 'user', 'warehouse', 'items.sparePart', 'items.vehicleModel')->whereBetween('sale_date', [$dateFrom, $dateTo]);
        $scope($query);
        $typeScope($query);
        $sales = $query->latest('sale_date')->paginate(25)->withQueryString();

        $summaryQ = Sale::completed()->whereBetween('sale_date', [$dateFrom, $dateTo]);
        $scope($summaryQ);
        $typeScope($summaryQ);
        $summary = $summaryQ->selectRaw('
            COUNT(*) as total_invoices,
            SUM(total) as gross_revenue,
            SUM(discount) as total_discounts,
            SUM(tax) as total_tax,
            SUM(paid_amount) as total_collected,
            SUM(balance) as total_outstanding
        ')->first();

        // Deduct approved returns from summary figures
        $returnsTotal = DB::table('sale_returns as sr')
            ->join('sales as s', 'sr.sale_id', '=', 's.id')
            ->where('sr.status', 'approved')
            ->whereBetween('s.sale_date', [$dateFrom, $dateTo])
            ->whereIn('s.warehouse_id', $accessibleIds)
            ->when($warehouseId, fn($q) => $q->where('s.warehouse_id', $warehouseId))
            ->sum('sr.total_amount');
        if ($summary) {
            $summary->gross_revenue    = max(0, $summary->gross_revenue    - $returnsTotal);
            $summary->total_collected  = max(0, $summary->total_collected  - $returnsTotal);
        }

        $daily = Sale::completed()->whereBetween('sale_date', [$dateFrom, $dateTo]);
        $scope($daily);
        $typeScope($daily);
        $daily = $daily->selectRaw('DATE(sale_date) as date, SUM(total) as total, COUNT(*) as count')
            ->groupBy('date')->orderBy('date')->get();

        // ── Daily revenue split by item type for chart ───────────────────
        $dailyItemType = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->where('s.status', 'completed')
            ->whereBetween('s.sale_date', [$dateFrom, $dateTo])
            ->whereIn('s.warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('s.user_id', $user->id))
            ->when($warehouseId, fn($q) => $q->where('s.warehouse_id', $warehouseId))
            ->selectRaw('DATE(s.sale_date) as date, si.item_type, SUM(si.total) as revenue')
            ->groupByRaw('DATE(s.sale_date), si.item_type')
            ->orderByRaw('DATE(s.sale_date)')
            ->get()
            ->groupBy('date');

        // Attach per-type revenue to each daily row
        $daily = $daily->map(function ($row) use ($dailyItemType) {
            $byType = $dailyItemType[$row->date] ?? collect();
            $row->vehicle_revenue    = (float) ($byType->firstWhere('item_type', 'vehicle')?->revenue    ?? 0);
            $row->spare_part_revenue = (float) ($byType->firstWhere('item_type', 'spare_part')?->revenue ?? 0);
            return $row;
        });

        // ── Net Sales Profit per day (selling price − COGS) ──────────────
        $profitRows = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->leftJoin('vehicle_models as vm', 'si.vehicle_model_id', '=', 'vm.id')
            ->where('s.status', 'completed')
            ->whereBetween('s.sale_date', [$dateFrom, $dateTo])
            ->whereIn('s.warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('s.user_id', $user->id))
            ->when($warehouseId, fn($q) => $q->where('s.warehouse_id', $warehouseId))
            ->when($itemType, fn($q) => $q->where('si.item_type', $itemType))
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


        // Deduct profit lost from approved returns
        $returnedProfit = DB::table('sale_return_items as sri')
            ->join('sale_returns as sr', 'sri.sale_return_id', '=', 'sr.id')
            ->join('sales as s', 'sr.sale_id', '=', 's.id')
            ->leftJoin('vehicle_models as vm', 'sri.vehicle_model_id', '=', 'vm.id')
            ->where('sr.status', 'approved')
            ->whereBetween('s.sale_date', [$dateFrom, $dateTo])
            ->whereIn('s.warehouse_id', $accessibleIds)
            ->when($warehouseId, fn($q) => $q->where('s.warehouse_id', $warehouseId))
            ->when($itemType, fn($q) => $q->where('sri.item_type', $itemType))
            ->selectRaw("SUM(sri.quantity * (sri.unit_price - COALESCE(CASE
                WHEN sri.item_type = 'vehicle' THEN vm.buying_price
                WHEN sri.item_type = 'spare_part' THEN (
                    SELECT pi2.unit_price FROM purchase_items pi2
                    JOIN purchases p2 ON pi2.purchase_id = p2.id
                    WHERE pi2.spare_part_id = sri.spare_part_id
                    ORDER BY p2.purchase_date DESC LIMIT 1)
                ELSE 0 END, 0))) as returned_profit")
            ->value('returned_profit');
        $totalProfit = max(0, $totalProfit - (float)($returnedProfit ?? 0));
        // ── Payment method breakdown ──────────────────────────────────────
        $paymentBase = Sale::completed()->whereBetween('sale_date', [$dateFrom, $dateTo]);
        $scope($paymentBase);
        $typeScope($paymentBase);
        $paymentBreakdown = $paymentBase
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total) as revenue, SUM(paid_amount) as collected, SUM(balance) as outstanding')
            ->groupBy('payment_method')
            ->orderBy('revenue', 'desc')
            ->get()
            ->keyBy('payment_method');

        // Known methods in display order
        $paymentMethods = [
            'cash'          => ['label' => 'Cash',          'icon' => 'fa-money-bill-wave',  'color' => 'success'],
            'bank_transfer' => ['label' => 'Bank Transfer', 'icon' => 'fa-building-columns', 'color' => 'info'],
            'cheque'        => ['label' => 'Cheque',        'icon' => 'fa-money-check',      'color' => 'primary'],
            'credit'        => ['label' => 'Credit',        'icon' => 'fa-credit-card',      'color' => 'warning'],
        ];

        // Also attach per-day payment method data for chart
        $dailyByMethod = DB::table('sales as s')
            ->where('s.status', 'completed')
            ->whereBetween('s.sale_date', [$dateFrom, $dateTo])
            ->whereIn('s.warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('s.user_id', $user->id))
            ->when($warehouseId, fn($q) => $q->where('s.warehouse_id', $warehouseId))
            ->when($itemType, fn($q) => $q->whereExists(function ($sub) use ($itemType) {
                $sub->select(DB::raw(1))
                    ->from('sale_items')
                    ->whereColumn('sale_items.sale_id', 's.id')
                    ->where('sale_items.item_type', $itemType);
            }))
            ->selectRaw('DATE(s.sale_date) as date, s.payment_method, SUM(s.total) as revenue')
            ->groupByRaw('DATE(s.sale_date), s.payment_method')
            ->orderByRaw('DATE(s.sale_date)')
            ->get()
            ->groupBy('date');

        return view('reports.sales', compact(
            'sales', 'summary', 'daily', 'totalProfit',
            'paymentBreakdown', 'paymentMethods', 'dailyByMethod',
            'itemType', 'dateFrom', 'dateTo', 'warehouses', 'warehouseId'
        ));
    }

    /* ── Sales Report — Export helpers ───────────────────────────────── */
    private function buildSalesExportData(Request $request): array
    {
        $dateFrom      = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo        = $request->date_to   ?? now()->format('Y-m-d');
        $warehouseId   = $request->warehouse_id ? (int) $request->warehouse_id : null;
        $itemType      = in_array($request->item_type, ['spare_part', 'vehicle']) ? $request->item_type : null;
        $user          = auth()->user();
        $accessibleIds = $user->accessibleWarehouseIds();
        $warehouses    = $user->accessibleWarehouses()->get();
        if ($warehouseId && ! in_array($warehouseId, $accessibleIds)) { $warehouseId = null; }

        $scope     = fn($q) => $q->whereIn('warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q2) => $q2->where('user_id', $user->id))
            ->when($warehouseId, fn($q2) => $q2->where('warehouse_id', $warehouseId));
        $typeScope = fn($q) => $q->when($itemType, fn($q2) => $q2->whereHas('items', fn($q3) => $q3->where('item_type', $itemType)));

        $salesQ = Sale::completed()->with('customer', 'user', 'warehouse', 'items')
            ->whereBetween('sale_date', [$dateFrom, $dateTo]);
        $scope($salesQ); $typeScope($salesQ);
        $sales = $salesQ->latest('sale_date')->get(); // all records for export

        $summaryQ = Sale::completed()->whereBetween('sale_date', [$dateFrom, $dateTo]);
        $scope($summaryQ); $typeScope($summaryQ);
        $summary = $summaryQ->selectRaw('COUNT(*) as total_invoices, SUM(total) as gross_revenue, SUM(discount) as total_discounts, SUM(tax) as total_tax, SUM(paid_amount) as total_collected, SUM(balance) as total_outstanding')->first();

        // Total profit
        $profitRows = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->leftJoin('vehicle_models as vm', 'si.vehicle_model_id', '=', 'vm.id')
            ->where('s.status', 'completed')
            ->whereBetween('s.sale_date', [$dateFrom, $dateTo])
            ->whereIn('s.warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('s.user_id', $user->id))
            ->when($warehouseId, fn($q) => $q->where('s.warehouse_id', $warehouseId))
            ->when($itemType, fn($q) => $q->where('si.item_type', $itemType))
            ->selectRaw("SUM(si.quantity * (si.unit_price - COALESCE(CASE WHEN si.item_type = 'vehicle' THEN vm.buying_price WHEN si.item_type = 'spare_part' THEN (SELECT pi2.unit_price FROM purchase_items pi2 JOIN purchases p2 ON pi2.purchase_id = p2.id WHERE pi2.spare_part_id = si.spare_part_id ORDER BY p2.purchase_date DESC LIMIT 1) ELSE 0 END, 0))) as profit")
            ->value('profit');

        $warehouseName = $warehouseId ? $warehouses->find($warehouseId)?->name : null;

        return compact('sales', 'summary', 'dateFrom', 'dateTo', 'itemType', 'warehouseName', 'profitRows');
    }

    public function exportSalesExcel(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = $this->buildSalesExportData($request);
        $export = new \App\Exports\SalesReportExport(
            $data['sales'],
            $data['summary'],
            (float) ($data['profitRows'] ?? 0),
            $data['dateFrom'],
            $data['dateTo'],
            $data['itemType'],
            $data['warehouseName']
        );
        return $export->download();
    }

    public function exportSalesPdf(Request $request)
    {
        $data = $this->buildSalesExportData($request);
        $pdf  = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.sales-pdf', [
            'sales'         => $data['sales'],
            'summary'       => $data['summary'],
            'totalProfit'   => (float) ($data['profitRows'] ?? 0),
            'dateFrom'      => $data['dateFrom'],
            'dateTo'        => $data['dateTo'],
            'itemType'      => $data['itemType'],
            'warehouseName' => $data['warehouseName'],
        ])->setPaper('a4', 'landscape');

        $filename = 'sales-report-' . $data['dateFrom'] . '-' . $data['dateTo'] . '.pdf';
        return $pdf->download($filename);
    }

    public function exportSalesItemsExcel(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data      = $this->buildSalesExportData($request);
        $dateFrom  = $data['dateFrom'];
        $dateTo    = $data['dateTo'];
        $itemType  = $data['itemType'];
        $warehouse = $data['warehouseName'];

        // Flatten all sale items
        $rows = [];
        foreach ($data['sales'] as $sale) {
            foreach ($sale->items as $item) {
                if ($itemType && $item->item_type !== $itemType) continue;
                $rows[] = [
                    $sale->invoice_number,
                    $sale->customer_name ?? 'Walk-in',
                    $sale->sale_date->format('M d, Y'),
                    ucfirst(str_replace('_', ' ', $item->item_type)),
                    $item->item_name,
                    $item->quantity,
                    number_format($item->unit_price, 2),
                    number_format($item->total, 2),
                    ucfirst(str_replace('_', ' ', $sale->payment_method ?? '')),
                    $sale->warehouse?->name ?? '—',
                    $sale->user->name,
                ];
            }
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sales Items');

        // Title
        $sheet->mergeCells('A1:K1');
        $sheet->setCellValue('A1', 'Sales Items Report — ' . \Carbon\Carbon::parse($dateFrom)->format('M d, Y') . ' to ' . \Carbon\Carbon::parse($dateTo)->format('M d, Y'));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF6366F1']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        // Headers
        $headers = ['Invoice', 'Customer', 'Date', 'Type', 'Item Name', 'Qty', 'Unit Price (Br)', 'Total (Br)', 'Payment', 'Warehouse', 'By'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}2", $h);
            $sheet->getStyle("{$col}2")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ]);
        }

        // Data rows
        foreach ($rows as $r => $row) {
            foreach ($row as $i => $val) {
                $col = chr(65 + $i);
                $sheet->setCellValue("{$col}" . ($r + 3), $val);
            }
            if ($r % 2 === 0) {
                $sheet->getStyle("A" . ($r + 3) . ":K" . ($r + 3))->applyFromArray([
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
                ]);
            }
        }

        // Column widths
        foreach ([16, 20, 12, 12, 28, 6, 16, 16, 14, 18, 14] as $i => $w) {
            $sheet->getColumnDimension(chr(65 + $i))->setWidth($w);
        }

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'sales-items-' . $dateFrom . '-' . $dateTo . '.xlsx';
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function exportSalesItemsPdf(Request $request)
    {
        $data = $this->buildSalesExportData($request);
        $pdf  = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.sales-items-pdf', [
            'sales'         => $data['sales'],
            'dateFrom'      => $data['dateFrom'],
            'dateTo'        => $data['dateTo'],
            'itemType'      => $data['itemType'],
            'warehouseName' => $data['warehouseName'],
        ])->setPaper('a4', 'landscape');
        $filename = 'sales-items-' . $data['dateFrom'] . '-' . $data['dateTo'] . '.pdf';
        return $pdf->download($filename);
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


        // Subtract approved returns per vehicle model
        $vehicleReturnMap = DB::table('sale_return_items as sri')
            ->join('sale_returns as sr', 'sri.sale_return_id', '=', 'sr.id')
            ->join('sales as s', 'sr.sale_id', '=', 's.id')
            ->where('sr.status', 'approved')
            ->where('sri.item_type', 'vehicle')
            ->whereBetween('s.sale_date', [$dateFrom, $dateTo])
            ->whereIn('s.warehouse_id', $accessibleIds)
            ->when($warehouseId, fn($q) => $q->where('s.warehouse_id', $warehouseId))
            ->selectRaw('sri.vehicle_model_id, SUM(sri.quantity) as ret_qty, SUM(sri.total) as ret_revenue')
            ->groupBy('sri.vehicle_model_id')
            ->get()->keyBy('vehicle_model_id');
        $vehicles = $vehicles->map(function ($v) use ($vehicleReturnMap) {
            $ret = $vehicleReturnMap[$v->id] ?? null;
            if ($ret) {
                $v->qty_sold -= $ret->ret_qty;
                $v->revenue  -= $ret->ret_revenue;
                $v->cost      = max(0, $v->qty_sold) * $v->buying_price;
                $v->profit    = $v->revenue - $v->cost;
            }
            return $v;
        })->filter(fn($v) => $v->qty_sold > 0);

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
            ->join('units as u', 'sp.unit_id', '=', 'u.id')
            ->where('si.item_type', 'spare_part')
            ->where('s.status', 'completed')
            ->whereBetween('s.sale_date', [$dateFrom, $dateTo])
            ->whereIn('s.warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('s.user_id', $user->id))
            ->when($warehouseId, fn($q) => $q->where('s.warehouse_id', $warehouseId))
            ->selectRaw('
                sp.id, sp.name, sp.part_number, sp.buying_price,
                u.abbreviation as unit,
                SUM(si.quantity) as qty_sold,
                SUM(si.total) as revenue,
                SUM(si.quantity * sp.buying_price) as cost,
                SUM(si.total - (si.quantity * sp.buying_price)) as profit
            ')
            ->groupBy('sp.id', 'sp.name', 'sp.part_number', 'sp.buying_price', 'u.abbreviation')
            ->orderByDesc('qty_sold')->get();

        // Subtract approved returns per spare part
        $partReturnMap = DB::table('sale_return_items as sri')
            ->join('sale_returns as sr', 'sri.sale_return_id', '=', 'sr.id')
            ->join('sales as s', 'sr.sale_id', '=', 's.id')
            ->where('sr.status', 'approved')
            ->where('sri.item_type', 'spare_part')
            ->whereBetween('s.sale_date', [$dateFrom, $dateTo])
            ->whereIn('s.warehouse_id', $accessibleIds)
            ->when($warehouseId, fn($q) => $q->where('s.warehouse_id', $warehouseId))
            ->selectRaw('sri.spare_part_id, SUM(sri.quantity) as ret_qty, SUM(sri.total) as ret_revenue')
            ->groupBy('sri.spare_part_id')
            ->get()->keyBy('spare_part_id');
        $parts = $parts->map(function ($p) use ($partReturnMap) {
            $ret = $partReturnMap[$p->id] ?? null;
            if ($ret) {
                $p->qty_sold -= $ret->ret_qty;
                $p->revenue  -= $ret->ret_revenue;
                $p->cost      = max(0, $p->qty_sold) * $p->buying_price;
                $p->profit    = $p->revenue - $p->cost;
            }
            return $p;
        })->filter(fn($p) => $p->qty_sold > 0);


        // Attach compatible vehicle names to each part
        $partIds = $parts->pluck('id');
        $vmMap = DB::table('spare_part_vehicle_model as spvm')
            ->join('vehicle_models as vm', 'spvm.vehicle_model_id', '=', 'vm.id')
            ->whereIn('spvm.spare_part_id', $partIds)
            ->selectRaw('spvm.spare_part_id, GROUP_CONCAT(vm.brand, " ", vm.model_name ORDER BY vm.brand SEPARATOR ", ") as vehicles')
            ->groupBy('spvm.spare_part_id')
            ->pluck('vehicles', 'spare_part_id');
        $parts->each(fn($p) => $p->vehicles = $vmMap[$p->id] ?? '—');

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

            $byCat = DB::table('spare_part_vehicle_model as spvm')
                ->join('vehicle_models as vm', 'spvm.vehicle_model_id', '=', 'vm.id')
                ->join('warehouse_spare_part_stock as ws', 'spvm.spare_part_id', '=', 'ws.spare_part_id')
                ->where('ws.warehouse_id', $warehouseId)
                ->selectRaw('vm.id as model_id, CONCAT(vm.brand, " ", vm.model_name) as name, COUNT(DISTINCT spvm.spare_part_id) as parts_count, SUM(ws.current_stock) as total_qty')
                ->groupBy('vm.id', 'vm.brand', 'vm.model_name')->orderByDesc('total_qty')
                ->get()
                ->map(function ($row) use ($warehouseId) {
                    $partIds = DB::table('spare_part_vehicle_model as spvm')
                        ->join('warehouse_spare_part_stock as ws', 'spvm.spare_part_id', '=', 'ws.spare_part_id')
                        ->where('ws.warehouse_id', $warehouseId)
                        ->where('spvm.vehicle_model_id', $row->model_id)
                        ->pluck('spvm.spare_part_id')->unique()->toArray();
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

            $byCat = DB::table('spare_part_vehicle_model as spvm')
                ->join('vehicle_models as vm', 'spvm.vehicle_model_id', '=', 'vm.id')
                ->join('spare_parts as sp', 'spvm.spare_part_id', '=', 'sp.id')
                ->selectRaw('vm.id as model_id, CONCAT(vm.brand, " ", vm.model_name) as name, COUNT(DISTINCT spvm.spare_part_id) as parts_count, SUM(sp.current_stock) as total_qty')
                ->groupBy('vm.id', 'vm.brand', 'vm.model_name')->orderByDesc('total_qty')
                ->get()
                ->map(function ($row) {
                    $partIds  = DB::table('spare_part_vehicle_model')->where('vehicle_model_id', $row->model_id)->pluck('spare_part_id')->unique()->toArray();
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
                ->join('units as u', 'sp.unit_id', '=', 'u.id')
                ->where('ws.warehouse_id', $warehouseId)
                ->where('ws.current_stock', '>', 0)
                ->whereColumn('ws.current_stock', '<=', 'ws.reorder_level')
                ->where('sp.status', 'active')
                ->selectRaw('sp.id, sp.name, sp.part_number, u.abbreviation as unit_abbr, ws.current_stock, ws.reorder_level, (SELECT GROUP_CONCAT(vm2.brand, " ", vm2.model_name ORDER BY vm2.brand SEPARATOR ", ") FROM spare_part_vehicle_model spvm2 JOIN vehicle_models vm2 ON spvm2.vehicle_model_id = vm2.id WHERE spvm2.spare_part_id = sp.id) as vehicles')
                ->orderBy('ws.current_stock')->get();

            $outParts = DB::table('warehouse_spare_part_stock as ws')
                ->join('spare_parts as sp', 'ws.spare_part_id', '=', 'sp.id')
                ->join('units as u', 'sp.unit_id', '=', 'u.id')
                ->where('ws.warehouse_id', $warehouseId)
                ->where('ws.current_stock', '<=', 0)
                ->where('sp.status', 'active')
                ->selectRaw('sp.id, sp.name, sp.part_number, u.abbreviation as unit_abbr, ws.current_stock, ws.reorder_level, (SELECT GROUP_CONCAT(vm2.brand, " ", vm2.model_name ORDER BY vm2.brand SEPARATOR ", ") FROM spare_part_vehicle_model spvm2 JOIN vehicle_models vm2 ON spvm2.vehicle_model_id = vm2.id WHERE spvm2.spare_part_id = sp.id) as vehicles')
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
            ->join('units as u', 'sp.unit_id', '=', 'u.id')
            ->whereIn('ws.warehouse_id', $accessibleIds)
            ->where('ws.current_stock', '>', 0)
            ->whereColumn('ws.current_stock', '<=', 'ws.reorder_level')
            ->where('sp.status', 'active')
            ->selectRaw('sp.id, sp.name, sp.part_number, u.abbreviation as unit_abbr,
                         MAX(ws.current_stock) as current_stock, MAX(ws.reorder_level) as reorder_level,
                         (SELECT GROUP_CONCAT(vm2.brand, " ", vm2.model_name ORDER BY vm2.brand SEPARATOR ", ") FROM spare_part_vehicle_model spvm2 JOIN vehicle_models vm2 ON spvm2.vehicle_model_id = vm2.id WHERE spvm2.spare_part_id = sp.id) as vehicles')
            ->groupBy('sp.id', 'sp.name', 'sp.part_number', 'u.abbreviation')
            ->orderBy('current_stock')
            ->get();

        $outParts = DB::table('warehouse_spare_part_stock as ws')
            ->join('spare_parts as sp', 'ws.spare_part_id', '=', 'sp.id')
            ->join('units as u', 'sp.unit_id', '=', 'u.id')
            ->whereIn('ws.warehouse_id', $accessibleIds)
            ->where('ws.current_stock', '<=', 0)
            ->where('sp.status', 'active')
            ->selectRaw('sp.id, sp.name, sp.part_number, u.abbreviation as unit_abbr, ws.current_stock, ws.reorder_level,
                         (SELECT GROUP_CONCAT(vm2.brand, " ", vm2.model_name ORDER BY vm2.brand SEPARATOR ", ") FROM spare_part_vehicle_model spvm2 JOIN vehicle_models vm2 ON spvm2.vehicle_model_id = vm2.id WHERE spvm2.spare_part_id = sp.id) as vehicles')
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

        // Item type breakdown (vehicles vs spare parts)
        $itemBreakdown = DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->whereBetween('p.purchase_date', [$dateFrom, $dateTo])
            ->whereIn('p.warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('p.user_id', $user->id))
            ->when($warehouseId, fn($q) => $q->where('p.warehouse_id', $warehouseId))
            ->selectRaw("CASE WHEN pi.vehicle_model_id IS NOT NULL THEN 'vehicle' ELSE 'spare_part' END as item_type, SUM(pi.quantity) as qty, SUM(pi.total) as total")
            ->groupByRaw("CASE WHEN pi.vehicle_model_id IS NOT NULL THEN 'vehicle' ELSE 'spare_part' END")
            ->get()->keyBy('item_type');

        // Daily split by item type for chart
        $dailyRows = DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->whereBetween('p.purchase_date', [$dateFrom, $dateTo])
            ->whereIn('p.warehouse_id', $accessibleIds)
            ->when(! $user->seesAllUsers(), fn($q) => $q->where('p.user_id', $user->id))
            ->when($warehouseId, fn($q) => $q->where('p.warehouse_id', $warehouseId))
            ->selectRaw("DATE(p.purchase_date) as date, CASE WHEN pi.vehicle_model_id IS NOT NULL THEN 'vehicle' ELSE 'spare_part' END as item_type, SUM(pi.total) as total")
            ->groupByRaw("DATE(p.purchase_date), CASE WHEN pi.vehicle_model_id IS NOT NULL THEN 'vehicle' ELSE 'spare_part' END")
            ->orderByRaw('DATE(p.purchase_date)')
            ->get()->groupBy('date');

        $chartDates      = $dailyRows->keys()->sort()->values();
        $chartLabels     = $chartDates->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'));
        $chartVehicles   = $chartDates->map(fn($d) => (float) ($dailyRows[$d]?->firstWhere('item_type','vehicle')?->total    ?? 0));
        $chartSpareParts = $chartDates->map(fn($d) => (float) ($dailyRows[$d]?->firstWhere('item_type','spare_part')?->total ?? 0));

        return view('reports.purchases', compact(
            'purchases', 'summary', 'bySupplier',
            'itemBreakdown', 'chartLabels', 'chartVehicles', 'chartSpareParts',
            'dateFrom', 'dateTo', 'warehouses', 'warehouseId'
        ));
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
