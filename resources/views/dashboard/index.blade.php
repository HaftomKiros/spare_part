@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')

{{-- -- Welcome Banner -------------------------------- --}}
<div class="welcome-banner mb-4">
    <div class="wb-deco"></div>
    <div class="wb-deco2"></div>
    <i class="fa-solid fa-gears wb-icon"></i>

    <div class="row align-items-center g-3">
        <div class="col-12 col-md-8">
            <p class="wb-sub mb-1">
                <i class="fa fa-sun me-1"></i>
                {{ now()->format('l, F j, Y') }}
            </p>
            <h1 class="wb-title">Welcome back, {{ auth()->user()->name }} 👋</h1>
            <p class="wb-sub">
                Here's what's happening at <strong>Abush Spare Part</strong> today.
                @if(!empty($hasFilter) && $hasFilter)
                    &mdash; <span class="badge bg-light text-dark"><i class="fa fa-warehouse me-1"></i>{{ $filterLabels ?? '' }}</span>
                @endif
            </p>
            <div class="wb-actions">
                <a href="{{ route('sales.create') }}" class="wb-btn wb-btn-white">
                    <i class="fa fa-plus"></i> New Sale
                </a>
                <a href="{{ route('purchases.create') }}" class="wb-btn wb-btn-outline">
                    <i class="fa fa-file-invoice"></i> New Purchase
                </a>
                <a href="{{ route('reports.profit') }}" class="wb-btn wb-btn-outline">
                    <i class="fa fa-chart-line"></i> View Profit
                </a>
            </div>
        </div>
        <div class="col-12 col-md-4 text-md-end d-none d-md-block">
            <div style="font-size:4rem;opacity:.15;line-height:1">
                <i class="fa-solid fa-motorcycle"></i>
            </div>
        </div>
    </div>
</div>

{{-- Stat Cards Row --}}
<div class="row g-3 mb-4">

    {{-- Today's Sales --}}
    <div class="col-6 col-md-4 col-xl-4">
        <div class="stat-card brand">
            <div class="stat-icon brand"><i class="fa-solid fa-receipt"></i></div>
            <div class="stat-value">
                <span class="stat-currency">Br</span>{{ number_format($stats['today_sales'], 0) }}
            </div>
            <div class="stat-label">Today's Sales</div>
            <div class="stat-change {{ $stats['today_sales_count'] > 0 ? 'up' : 'neutral' }}">
                <i class="fa fa-{{ $stats['today_sales_count'] > 0 ? 'arrow-up' : 'minus' }}"></i>
                {{ $stats['today_sales_count'] }} invoice{{ $stats['today_sales_count'] != 1 ? 's' : '' }}
            </div>
            <i class="fa-solid fa-receipt watermark"></i>
        </div>
    </div>

    {{-- Month Sales --}}
    <div class="col-6 col-md-4 col-xl-4">
        <div class="stat-card success">
            <div class="stat-icon success"><i class="fa-solid fa-chart-line"></i></div>
            <div class="stat-value">
                <span class="stat-currency">Br</span>{{ number_format($stats['month_sales'], 0) }}
            </div>
            <div class="stat-label">Month Sales</div>
            <div class="stat-change neutral">
                <i class="fa fa-calendar"></i>
                {{ now()->format('M Y') }}
            </div>
            <i class="fa-solid fa-chart-line watermark"></i>
        </div>
    </div>

    {{-- Month Profit --}}
    <div class="col-6 col-md-4 col-xl-4">
        <div class="stat-card purple">
            <div class="stat-icon purple"><i class="fa-solid fa-sack-dollar"></i></div>
            <div class="stat-value {{ $stats['month_profit'] < 0 ? 'text-danger' : '' }}">
                <span class="stat-currency">Br</span>{{ number_format($stats['month_profit'], 0) }}
            </div>
            <div class="stat-label">Month Profit</div>
            <div class="stat-change {{ $stats['month_profit'] >= 0 ? 'up' : 'down' }}">
                <i class="fa fa-{{ $stats['month_profit'] >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                Net earnings
            </div>
            <i class="fa-solid fa-sack-dollar watermark"></i>
        </div>
    </div>

    {{-- Purchases --}}
    <div class="col-6 col-md-4 col-xl-4">
        <div class="stat-card warning">
            <div class="stat-icon warning"><i class="fa-solid fa-truck"></i></div>
            <div class="stat-value">
                <span class="stat-currency">Br</span>{{ number_format($stats['month_purchases'], 0) }}
            </div>
            <div class="stat-label">Month Purchases</div>
            <div class="stat-change neutral">
                <i class="fa fa-box-open"></i>
                Total cost
            </div>
            <i class="fa-solid fa-truck watermark"></i>
        </div>
    </div>

    {{-- Stock Value --}}
    <div class="col-6 col-md-4 col-xl-4">
        <div class="stat-card info">
            <div class="stat-icon info"><i class="fa-solid fa-warehouse"></i></div>
            <div class="stat-value">
                <span class="stat-currency">Br</span>{{ number_format($stats['total_inventory_value'], 0) }}
            </div>
            <div class="stat-label">Stock Value</div>
            <div class="stat-change neutral">
                <i class="fa fa-boxes-stacked"></i>
                Total inventory
            </div>
            <i class="fa-solid fa-warehouse watermark"></i>
        </div>
    </div>

    {{-- Low Stock --}}
    <div class="col-6 col-md-4 col-xl-4">
        <div class="stat-card danger">
            <div class="stat-icon danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="stat-value">{{ $stats['low_stock_parts'] + $stats['low_stock_vehicles'] }}</div>
            <div class="stat-label">Low Stock</div>
            <a href="{{ route('reports.low-stock') }}" class="stat-change down text-decoration-none">
                <i class="fa fa-arrow-right"></i> View alerts
            </a>
            <i class="fa-solid fa-triangle-exclamation watermark"></i>
        </div>
    </div>

</div>

{{-- -- Count Strip ----------------------------------- --}}
<div class="count-strip mb-4">
    <div class="row g-0">
        <div class="col-3">
            <div class="count-item">
                <div class="count-num">{{ $stats['total_vehicles'] }}</div>
                <div class="count-lbl"><i class="fa fa-motorcycle me-1 d-none d-sm-inline"></i>Vehicles</div>
            </div>
        </div>
        <div class="col-3">
            <div class="count-item">
                <div class="count-num">{{ $stats['total_spare_parts'] }}</div>
                <div class="count-lbl"><i class="fa fa-gears me-1 d-none d-sm-inline"></i>Spare Parts</div>
            </div>
        </div>
        <div class="col-3">
            <div class="count-item">
                <div class="count-num">{{ $stats['total_customers'] }}</div>
                <div class="count-lbl"><i class="fa fa-users me-1 d-none d-sm-inline"></i>Customers</div>
            </div>
        </div>
        <div class="col-3">
            <div class="count-item">
                <div class="count-num">{{ $stats['total_suppliers'] }}</div>
                <div class="count-lbl"><i class="fa fa-truck me-1 d-none d-sm-inline"></i>Suppliers</div>
            </div>
        </div>
    </div>
</div>

{{-- -- Charts Row ------------------------------------ --}}
<div class="row g-3 mb-4">

    {{-- Sales Trend --}}
    <div class="col-12 col-xl-8">
        <div class="card h-100">
            <div class="card-header">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:30px;height:30px;background:var(--brand-light);border-radius:8px;display:flex;align-items:center;justify-content:center">
                        <i class="fa fa-chart-area" style="color:var(--brand-1);font-size:.85rem"></i>
                    </div>
                    <span>Sales - Last 7 Days</span>
                </div>
                <a href="{{ route('reports.sales') }}" class="btn btn-sm btn-outline-primary">Full Report</a>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Sales Mix Donut --}}
    <div class="col-12 col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:30px;height:30px;background:var(--brand-light);border-radius:8px;display:flex;align-items:center;justify-content:center">
                        <i class="fa fa-chart-pie" style="color:var(--brand-1);font-size:.85rem"></i>
                    </div>
                    <span>Sales Mix - {{ now()->format('M Y') }}</span>
                </div>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <div style="max-width:200px;width:100%">
                    <canvas id="salesMixChart"></canvas>
                </div>
                <div class="d-flex gap-4 mt-3 small">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-block rounded-circle" style="width:10px;height:10px;background:#5b4fcf"></span>
                        <span class="text-muted">Vehicles</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-block rounded-circle" style="width:10px;height:10px;background:#059669"></span>
                        <span class="text-muted">Spare Parts</span>
                    </div>
                </div>
                {{-- totals below donut --}}
                <div class="row w-100 mt-3 g-2 text-center">
                    <div class="col-6">
                        <div class="p-2 rounded-3" style="background:var(--brand-light)">
                            <div class="fw-bold" style="color:var(--brand-1);font-size:.95rem">Br {{ number_format($vehicleSales,0) }}</div>
                            <div class="small text-muted" style="font-size:.72rem">Vehicles</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded-3" style="background:#d1fae5">
                            <div class="fw-bold text-success" style="font-size:.95rem">Br {{ number_format($partSales,0) }}</div>
                            <div class="small text-muted" style="font-size:.72rem">Spare Parts</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- -- Recent Sales + Low Stock ---------------------- --}}
<div class="row g-3 mb-4">

    {{-- Recent Sales --}}
    <div class="col-12 col-xl-7">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:30px;height:30px;background:var(--brand-light);border-radius:8px;display:flex;align-items:center;justify-content:center">
                        <i class="fa fa-receipt" style="color:var(--brand-1);font-size:.85rem"></i>
                    </div>
                    <span>Recent Sales</span>
                </div>
                <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th class="d-none d-md-table-cell">Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentSales as $sale)
                        <tr>
                            <td>
                                <a href="{{ route('sales.show', $sale) }}" class="fw-semibold" style="color:var(--brand-1)">
                                    {{ $sale->invoice_number }}
                                </a>
                            </td>
                            <td class="text-muted small">{{ $sale->customer_name }}</td>
                            <td class="text-muted small d-none d-md-table-cell">
                                {{ $sale->sale_date->format('M d, Y') }}
                            </td>
                            <td class="fw-semibold">Br {{ number_format($sale->total, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $sale->payment_status_badge }}">
                                    {{ ucfirst($sale->payment_status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fa fa-receipt fa-2x d-block mb-2 opacity-25"></i>
                                No sales recorded yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Low Stock Alerts --}}
    <div class="col-12 col-xl-5">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:30px;height:30px;background:#fef3c7;border-radius:8px;display:flex;align-items:center;justify-content:center">
                        <i class="fa fa-triangle-exclamation" style="color:#d97706;font-size:.85rem"></i>
                    </div>
                    <span>Low Stock Alerts</span>
                    @if(($lowStockParts->count() + $lowStockVehicles->count()) > 0)
                        <span class="badge" style="background:#fef3c7;color:#92400e">
                            {{ $lowStockParts->count() + $lowStockVehicles->count() }}
                        </span>
                    @endif
                </div>
                <a href="{{ route('reports.low-stock') }}" class="btn btn-sm btn-outline-warning">View All</a>
            </div>
            <div class="card-body p-0">

                @if($lowStockVehicles->count() > 0)
                <div class="px-4 py-2 border-bottom">
                    <div class="divider-label mb-2 mt-1">Vehicles</div>
                    @foreach($lowStockVehicles as $vs)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                        <div>
                            @if(is_object($vs) && isset($vs->vehicleModel))
                                {{-- Eloquent VehicleStock model (global view) --}}
                                <div class="fw-medium small">{{ $vs->vehicleModel->full_name }}</div>
                                <div class="text-muted" style="font-size:.73rem">{{ $vs->vehicleModel->vehicleType->name }}</div>
                            @else
                                {{-- Raw DB result (per-warehouse view) --}}
                                <div class="fw-medium small">{{ $vs->brand }} {{ $vs->model_name }}{{ $vs->model_code ? ' ('.$vs->model_code.')' : '' }}</div>
                                <div class="text-muted" style="font-size:.73rem">{{ $vs->type_name }}</div>
                            @endif
                        </div>
                        <span class="stock-pill {{ $vs->current_stock <= 0 ? 'out' : 'low' }}">
                            {{ $vs->current_stock }} / {{ $vs->reorder_level }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif

                @if($lowStockParts->count() > 0)
                <div class="px-4 py-2">
                    <div class="divider-label mb-2 mt-1">Spare Parts</div>
                    @foreach($lowStockParts as $part)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                        <div>
                            <div class="fw-medium small">{{ $part->name }}</div>
                            <div class="text-muted" style="font-size:.73rem">{{ $part->part_number }}</div>
                        </div>
                        <span class="stock-pill {{ $part->current_stock <= 0 ? 'out' : 'low' }}">
                            {{ $part->current_stock }} {{ $part->unit?->abbreviation ?? '' }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif

                @if($lowStockParts->count() === 0 && $lowStockVehicles->count() === 0)
                <div class="text-center py-4 text-muted">
                    <i class="fa fa-check-circle fa-2x mb-2 d-block text-success opacity-75"></i>
                    All stock levels healthy!
                </div>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- -- Quick Actions --------------------------------- --}}
<div class="card mb-2">
    <div class="card-header">
        <div class="d-flex align-items-center gap-2">
            <div style="width:30px;height:30px;background:var(--brand-light);border-radius:8px;display:flex;align-items:center;justify-content:center">
                <i class="fa fa-bolt" style="color:var(--brand-1);font-size:.85rem"></i>
            </div>
            <span>Quick Actions</span>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-6 col-md-4 col-lg-2">
                <a href="{{ route('sales.create') }}"
                   class="d-flex flex-column align-items-center justify-content-center gap-2 p-3 rounded-3 text-decoration-none text-center h-100"
                   style="background:var(--brand-light);border:1.5px solid #c4b5fd;transition:all .2s"
                   onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(91,79,207,.2)'"
                   onmouseout="this.style.transform='';this.style.boxShadow=''">
                    <div style="width:42px;height:42px;background:var(--brand-gradient);border-radius:11px;display:flex;align-items:center;justify-content:center">
                        <i class="fa fa-plus text-white fs-5"></i>
                    </div>
                    <span class="small fw-semibold" style="color:var(--brand-1)">New Sale</span>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="{{ route('purchases.create') }}"
                   class="d-flex flex-column align-items-center justify-content-center gap-2 p-3 rounded-3 text-decoration-none text-center h-100"
                   style="background:#d1fae5;border:1.5px solid #6ee7b7;transition:all .2s"
                   onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(5,150,105,.2)'"
                   onmouseout="this.style.transform='';this.style.boxShadow=''">
                    <div style="width:42px;height:42px;background:linear-gradient(135deg,#059669,#34d399);border-radius:11px;display:flex;align-items:center;justify-content:center">
                        <i class="fa fa-file-invoice text-white fs-5"></i>
                    </div>
                    <span class="small fw-semibold text-success">New Purchase</span>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="{{ route('inventory.stock-in.create') }}"
                   class="d-flex flex-column align-items-center justify-content-center gap-2 p-3 rounded-3 text-decoration-none text-center h-100"
                   style="background:#fef3c7;border:1.5px solid #fde68a;transition:all .2s"
                   onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(217,119,6,.2)'"
                   onmouseout="this.style.transform='';this.style.boxShadow=''">
                    <div style="width:42px;height:42px;background:linear-gradient(135deg,#d97706,#fbbf24);border-radius:11px;display:flex;align-items:center;justify-content:center">
                        <i class="fa fa-arrow-down-to-bracket text-white fs-5"></i>
                    </div>
                    <span class="small fw-semibold text-warning">Stock Entry</span>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="{{ route('inventory.adjustments.create') }}"
                   class="d-flex flex-column align-items-center justify-content-center gap-2 p-3 rounded-3 text-decoration-none text-center h-100"
                   style="background:#dbeafe;border:1.5px solid #93c5fd;transition:all .2s"
                   onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(2,132,199,.2)'"
                   onmouseout="this.style.transform='';this.style.boxShadow=''">
                    <div style="width:42px;height:42px;background:linear-gradient(135deg,#0284c7,#38bdf8);border-radius:11px;display:flex;align-items:center;justify-content:center">
                        <i class="fa fa-sliders text-white fs-5"></i>
                    </div>
                    <span class="small fw-semibold text-info">Adjust Stock</span>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="{{ route('catalog.spare-parts.create') }}"
                   class="d-flex flex-column align-items-center justify-content-center gap-2 p-3 rounded-3 text-decoration-none text-center h-100"
                   style="background:#f3e8ff;border:1.5px solid #d8b4fe;transition:all .2s"
                   onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(124,58,237,.2)'"
                   onmouseout="this.style.transform='';this.style.boxShadow=''">
                    <div style="width:42px;height:42px;background:linear-gradient(135deg,#7c3aed,#c084fc);border-radius:11px;display:flex;align-items:center;justify-content:center">
                        <i class="fa fa-gears text-white fs-5"></i>
                    </div>
                    <span class="small fw-semibold" style="color:#7c3aed">Add Part</span>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="{{ route('reports.profit') }}"
                   class="d-flex flex-column align-items-center justify-content-center gap-2 p-3 rounded-3 text-decoration-none text-center h-100"
                   style="background:#fee2e2;border:1.5px solid #fca5a5;transition:all .2s"
                   onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(220,38,38,.15)'"
                   onmouseout="this.style.transform='';this.style.boxShadow=''">
                    <div style="width:42px;height:42px;background:linear-gradient(135deg,#dc2626,#f87171);border-radius:11px;display:flex;align-items:center;justify-content:center">
                        <i class="fa fa-sack-dollar text-white fs-5"></i>
                    </div>
                    <span class="small fw-semibold text-danger">Profit Report</span>
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

{{-- ── Warehouse FAB (Floating Action Button) ────────────── --}}
@if(isset($warehouses) && $warehouses->count() > 0)
<div id="whFab" style="display:none">

    {{-- Backdrop --}}
    <div id="whFabBackdrop"></div>

    {{-- Panel --}}
    <div id="whFabPanel">
        <div class="whfab-panel-header">
            <div class="d-flex align-items-center gap-2">
                <div class="whfab-icon-sm"><i class="fa fa-warehouse"></i></div>
                <div>
                    <div class="fw-bold" style="font-size:.9rem;color:#fff">Filter by Warehouse</div>
                    <div style="font-size:.72rem;color:rgba(255,255,255,.75)">Select one or more warehouses</div>
                </div>
            </div>
            <button type="button" id="whFabClose" class="whfab-close-btn">
                <i class="fa fa-xmark"></i>
            </button>
        </div>

        <form method="GET" action="{{ route('dashboard') }}" id="whFabForm" class="whfab-form">
            {{-- Apply button ABOVE the select so it's always visible --}}
            <div class="whfab-actions">
                <button type="submit" class="btn btn-primary flex-grow-1" id="whFabApply">
                    <i class="fa fa-filter me-1"></i>Apply Filter
                </button>
                @if(!empty($hasFilter))
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-xmark me-1"></i>Clear
                </a>
                @endif
            </div>

            <select name="warehouse_ids[]"
                    id="whFabSelect"
                    multiple
                    placeholder="Search warehouses...">
                @foreach($warehouses as $wh)
                    <option value="{{ $wh->id }}"
                        {{ in_array($wh->id, $warehouseIds ?? []) ? 'selected' : '' }}>
                        {{ $wh->name }}{{ $wh->is_default ? ' (Default)' : '' }}
                    </option>
                @endforeach
            </select>

            @if(!empty($hasFilter))
            <div class="whfab-active-badge">
                <i class="fa fa-circle-check me-1"></i>
                Filtered: {{ $filterLabels ?? '' }}
            </div>
            @endif
        </form>
    </div>

    {{-- FAB button --}}
    <button type="button" id="whFabBtn" title="Filter by Warehouse">
        <i class="fa fa-warehouse" id="whFabIcon"></i>
        @if(!empty($hasFilter))
        <span id="whFabBadge">{{ count($warehouseIds ?? []) }}</span>
        @endif
    </button>

</div>
@endif

@push('scripts')
<script>
// -- Sales Trend Line Chart -----------------------
const trendCtx = document.getElementById('salesTrendChart');
if (trendCtx) {
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                label: 'Sales (Br)',
                data: @json($chartData),
                borderColor: '#5b4fcf',
                backgroundColor: (ctx) => {
                    const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 260);
                    gradient.addColorStop(0, 'rgba(91,79,207,.18)');
                    gradient.addColorStop(1, 'rgba(91,79,207,0)');
                    return gradient;
                },
                borderWidth: 2.5,
                pointBackgroundColor: '#5b4fcf',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                tension: 0.4,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1c1735',
                    titleColor: '#fff',
                    bodyColor: '#c4b5fd',
                    borderColor: '#5b4fcf',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: ctx => ' Br ' + parseFloat(ctx.raw).toLocaleString('en-US', { minimumFractionDigits: 2 })
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(91,79,207,.06)' },
                    ticks: {
                        color: '#9ca3af',
                        font: { size: 11 },
                        callback: v => 'Br ' + v.toLocaleString()
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#9ca3af', font: { size: 11 } }
                }
            }
        }
    });
}

// -- Sales Mix Donut ------------------------------
const mixCtx = document.getElementById('salesMixChart');
if (mixCtx) {
    new Chart(mixCtx, {
        type: 'doughnut',
        data: {
            labels: ['Vehicles', 'Spare Parts'],
            datasets: [{
                data: [{{ $vehicleSales }}, {{ $partSales }}],
                backgroundColor: ['#5b4fcf', '#059669'],
                hoverBackgroundColor: ['#4a3fbe', '#047857'],
                borderWidth: 0,
                hoverOffset: 8,
            }]
        },
        options: {
            responsive: true,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1c1735',
                    titleColor: '#fff',
                    bodyColor: '#c4b5fd',
                    callbacks: {
                        label: ctx => ' Br ' + parseFloat(ctx.raw).toLocaleString('en-US', { minimumFractionDigits: 2 })
                    }
                }
            }
        }
    });
}
</script>

<script>
// -- Warehouse FAB ------------------------------------
(function () {
    var fab      = document.getElementById('whFab');
    var btn      = document.getElementById('whFabBtn');
    var panel    = document.getElementById('whFabPanel');
    var backdrop = document.getElementById('whFabBackdrop');
    var closeBtn = document.getElementById('whFabClose');
    var selEl    = document.getElementById('whFabSelect');
    var form     = document.getElementById('whFabForm');
    var applyBtn = document.getElementById('whFabApply');
    var fabIcon  = document.getElementById('whFabIcon');

    if (!btn || !panel || !selEl) return;

    // Show the FAB wrapper (was hidden to avoid flash before CSS loads)
    fab.style.display = 'block';

    // Init Tom Select
    var ts = new TomSelect(selEl, {
        plugins: ['remove_button', 'checkbox_options'],
        placeholder: 'Search warehouses...',
        closeAfterSelect: false,
        maxOptions: 200,
        dropdownParent: null,
        openOnFocus: true,
        render: {
            option: function (data, escape) {
                return '<div class="d-flex align-items-center gap-2 py-1">' +
                    '<i class="fa fa-warehouse" style="font-size:.72rem;color:#9d8ff0"></i>' +
                    '<span>' + escape(data.text) + '</span>' +
                    '</div>';
            },
            item: function (data, escape) {
                return '<div style="font-size:.8rem">' +
                    '<i class="fa fa-warehouse me-1" style="font-size:.68rem;color:#5b4fcf"></i>' +
                    escape(data.text) + '</div>';
            }
        }
    });

    // Show FAB button only after Tom Select is ready
    fab.classList.add('ready');

    function openPanel() {
        panel.classList.add('open');
        backdrop.classList.add('show');
        fabIcon.className = 'fa fa-xmark';
        setTimeout(function () { ts.focus(); }, 150);
    }

    function closePanel() {
        panel.classList.remove('open');
        backdrop.classList.remove('show');
        fabIcon.className = 'fa fa-warehouse';
    }

    btn.addEventListener('click', function () {
        panel.classList.contains('open') ? closePanel() : openPanel();
    });

    if (closeBtn) closeBtn.addEventListener('click', closePanel);
    backdrop.addEventListener('click', closePanel);

    // Show loader on submit
    if (form) {
        form.addEventListener('submit', function () {
            if (applyBtn) {
                applyBtn.disabled = true;
                applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading...';
            }
            var loader = document.getElementById('pageLoader');
            if (loader) { loader.style.display = 'flex'; loader.classList.remove('hide'); }
        });
    }
})();
</script>
@endpush
