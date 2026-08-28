@extends('layouts.app')
@section('title','Vehicles Report')
@section('breadcrumb')
    <li class="breadcrumb-item active">Reports</li>
    <li class="breadcrumb-item active">Vehicles</li>
@endsection
@section('content')

@include('partials.report-nav', ['active' => 'vehicles'])

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0" style="color:#1e293b">Vehicles Sales Report</h5>
        <div class="text-muted small">Vehicle models sold in the selected period</div>
    </div>
</div>

<div class="rpt-filter-card">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
        <div><label class="form-label">From</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
        </div>
        <div><label class="form-label">To</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
        </div>
        @include('partials.warehouse-filter')
        <div class="mt-auto"><button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-filter me-1"></i>Apply</button></div>
        @if($warehouseId)
        <div class="mt-auto ms-auto">
            <span class="rpt-period-badge"><i class="fa fa-warehouse"></i>{{ $warehouses->find($warehouseId)?->name }}</span>
        </div>
        @endif
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-4"><div class="stat-card"><div class="stat-icon bg-primary-soft"><i class="fa fa-motorcycle"></i></div><div class="stat-body"><div class="stat-value">{{ number_format($totalQty) }}</div><div class="stat-label">Units Sold</div></div></div></div>
    <div class="col-4"><div class="stat-card"><div class="stat-icon bg-success-soft"><i class="fa fa-chart-line"></i></div><div class="stat-body"><div class="stat-value">Br {{ number_format($totalRevenue,0) }}</div><div class="stat-label">Revenue</div></div></div></div>
    <div class="col-4"><div class="stat-card"><div class="stat-icon bg-purple-soft"><i class="fa fa-sack-dollar"></i></div><div class="stat-body"><div class="stat-value {{ $totalProfit >= 0 ? 'text-success' : 'text-danger' }}">Br {{ number_format($totalProfit,0) }}</div><div class="stat-label">Gross Profit</div></div></div></div>
</div>

<div class="card">
<div class="card-header d-flex align-items-center gap-2">
    <i class="fa fa-motorcycle text-primary"></i><span>Sales by Vehicle Model</span>
    <span class="badge bg-secondary-subtle text-secondary ms-auto" style="font-size:.72rem">{{ $vehicles->count() }} models</span>
</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Model</th><th>Type</th><th>Qty Sold</th><th>Revenue</th><th>Cost</th><th>Profit</th><th>Margin</th></tr></thead>
    <tbody>
        @forelse($vehicles as $v)
        @php $margin = $v->revenue > 0 ? round(($v->profit/$v->revenue)*100,1) : 0; @endphp
        <tr>
            <td>
                <div class="fw-semibold small">{{ $v->brand }} {{ $v->model_name }}</div>
                @if($v->model_code)<div class="text-muted" style="font-size:.72rem">{{ $v->model_code }}</div>@endif
            </td>
            <td><span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:.72rem;padding:3px 8px;border-radius:5px">{{ $v->type_name }}</span></td>
            <td class="fw-semibold text-primary">{{ number_format($v->qty_sold) }}</td>
            <td class="fw-semibold">Br {{ number_format($v->revenue,2) }}</td>
            <td class="text-muted small">Br {{ number_format($v->cost,2) }}</td>
            <td class="{{ $v->profit >= 0 ? 'text-success' : 'text-danger' }} fw-semibold">Br {{ number_format($v->profit,2) }}</td>
            <td>
                <div class="d-flex align-items-center gap-1">
                    <div class="progress flex-grow-1" style="height:4px;min-width:40px">
                        <div class="progress-bar bg-{{ $margin >= 0 ? 'success' : 'danger' }}" style="width:{{ min(100,abs($margin)) }}%"></div>
                    </div>
                    <span class="small {{ $margin >= 0 ? 'text-success' : 'text-danger' }}">{{ $margin }}%</span>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted py-5">
            <i class="fa fa-motorcycle fs-2 d-block mb-2 opacity-25"></i>No vehicle sales in this period.
        </td></tr>
        @endforelse
    </tbody>
    @if($vehicles->count())
    <tfoot class="table-light fw-bold">
        <tr>
            <td colspan="2">Total</td>
            <td>{{ number_format($totalQty) }}</td>
            <td>Br {{ number_format($totalRevenue,2) }}</td>
            <td class="text-muted">Br {{ number_format($vehicles->sum('cost'),2) }}</td>
            <td class="{{ $totalProfit >= 0 ? 'text-success' : 'text-danger' }}">Br {{ number_format($totalProfit,2) }}</td>
            <td class="{{ $totalProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ $totalRevenue > 0 ? round(($totalProfit/$totalRevenue)*100,1) : 0 }}%</td>
        </tr>
    </tfoot>
    @endif
</table>
</div>
</div>
@endsection
