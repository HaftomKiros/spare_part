@extends('layouts.app')
@section('title','Profit Report')
@section('breadcrumb')
    <li class="breadcrumb-item active">Reports</li>
    <li class="breadcrumb-item active">Profit</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'Profit & Loss Report','subtitle'=>'Gross profit analysis by period'])

<!-- Filters -->
<div class="card mb-4">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-auto">
        <label class="form-label small mb-1">Year</label>
        <select name="year" class="form-select form-select-sm">
            @foreach($years as $y)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small mb-1">Month (optional)</label>
        <select name="month" class="form-select form-select-sm">
            <option value="">All Year</option>
            @for($m=1;$m<=12;$m++)
                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromDate(null,$m,1)->format('F') }}</option>
            @endfor
        </select>
    </div>
    @include('partials.warehouse-filter')
    <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary mt-3"><i class="fa fa-filter me-1"></i>Apply</button></div>
    @if($warehouseId)
    <div class="col-auto ms-auto mt-3">
        <span class="badge bg-primary-subtle text-primary-emphasis"><i class="fa fa-warehouse me-1"></i>{{ $warehouses->find($warehouseId)?->name }}</span>
    </div>
    @endif
</form>
</div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon bg-primary-soft"><i class="fa fa-chart-line"></i></div><div class="stat-body"><div class="stat-value">Br {{ number_format($totalRevenue,0) }}</div><div class="stat-label">Total Revenue</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon bg-warning-soft"><i class="fa fa-boxes-stacked"></i></div><div class="stat-body"><div class="stat-value">Br {{ number_format($totalCost,0) }}</div><div class="stat-label">Total Cost</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon bg-{{ $totalProfit >= 0 ? 'success' : 'danger' }}-soft"><i class="fa fa-sack-dollar"></i></div><div class="stat-body"><div class="stat-value {{ $totalProfit >= 0 ? 'text-success' : 'text-danger' }}">Br {{ number_format($totalProfit,0) }}</div><div class="stat-label">Gross Profit</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon bg-purple-soft"><i class="fa fa-percent"></i></div><div class="stat-body"><div class="stat-value {{ $avgMargin >= 0 ? 'text-success' : 'text-danger' }}">{{ $avgMargin }}%</div><div class="stat-label">Profit Margin</div></div></div></div>
</div>

<!-- Monthly Chart -->
@if($monthly->count())
<div class="card mb-4">
<div class="card-header"><i class="fa fa-chart-bar me-2 text-primary"></i>Monthly Profit — {{ $year }}</div>
<div class="card-body"><div class="chart-container"><canvas id="profitChart"></canvas></div></div>
</div>
@endif

<!-- Monthly Table -->
<div class="card mb-4">
<div class="card-header"><i class="fa fa-calendar me-2 text-primary"></i>Monthly Breakdown</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Period</th><th>Revenue</th><th>Cost</th><th>Gross Profit</th><th>Margin</th></tr></thead>
    <tbody>
        @forelse($monthly as $row)
        <tr>
            <td class="fw-semibold">{{ $row->month_name }}</td>
            <td>Br {{ number_format($row->revenue,2) }}</td>
            <td class="text-muted">Br {{ number_format($row->cost,2) }}</td>
            <td class="{{ $row->profit >= 0 ? 'text-success' : 'text-danger' }} fw-semibold">Br {{ number_format($row->profit,2) }}</td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:6px">
                        <div class="progress-bar bg-{{ $row->margin >= 0 ? 'success' : 'danger' }}"
                             style="width:{{ min(100,abs($row->margin)) }}%"></div>
                    </div>
                    <span class="small {{ $row->margin >= 0 ? 'text-success' : 'text-danger' }}">{{ $row->margin }}%</span>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center text-muted py-4">No sales data for this period.</td></tr>
        @endforelse
    </tbody>
    @if($monthly->count())
    <tfoot class="table-light fw-bold">
        <tr>
            <td>Total</td>
            <td>Br {{ number_format($totalRevenue,2) }}</td>
            <td class="text-muted">Br {{ number_format($totalCost,2) }}</td>
            <td class="{{ $totalProfit >= 0 ? 'text-success' : 'text-danger' }}">Br {{ number_format($totalProfit,2) }}</td>
            <td class="{{ $avgMargin >= 0 ? 'text-success' : 'text-danger' }}">{{ $avgMargin }}%</td>
        </tr>
    </tfoot>
    @endif
</table>
</div>
</div>

<div class="row g-3">
<!-- Top Parts -->
<div class="col-12 col-md-6">
<div class="card">
<div class="card-header"><i class="fa fa-trophy me-2 text-warning"></i>Top Profitable Spare Parts</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Part</th><th>Qty</th><th>Revenue</th><th>Profit</th></tr></thead>
    <tbody>
        @forelse($topParts as $p)
        <tr>
            <td><div class="fw-semibold small">{{ $p->name }}</div><div class="text-muted" style="font-size:.72rem">{{ $p->part_number }}</div></td>
            <td class="text-muted">{{ $p->qty }}</td>
            <td class="text-muted small">Br {{ number_format($p->revenue,2) }}</td>
            <td class="text-success fw-semibold">Br {{ number_format($p->profit,2) }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="text-center text-muted py-3">No data.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</div>
</div>

<!-- Top Vehicles -->
<div class="col-12 col-md-6">
<div class="card">
<div class="card-header"><i class="fa fa-trophy me-2 text-primary"></i>Top Profitable Vehicles</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Model</th><th>Qty</th><th>Revenue</th><th>Profit</th></tr></thead>
    <tbody>
        @forelse($topVehicles as $v)
        <tr>
            <td class="fw-semibold small">{{ $v->brand }} {{ $v->model_name }}</td>
            <td class="text-muted">{{ $v->qty }}</td>
            <td class="text-muted small">Br {{ number_format($v->revenue,2) }}</td>
            <td class="text-success fw-semibold">Br {{ number_format($v->profit,2) }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="text-center text-muted py-3">No data.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</div>
</div>
</div>
@endsection
@push('scripts')
<script>
const ctx = document.getElementById('profitChart');
if(ctx) {
    new Chart(ctx,{
        type:'bar',
        data:{
            labels: @json($monthly->pluck('month_name')),
            datasets:[
                {label:'Revenue',data:@json($monthly->pluck('revenue')),backgroundColor:'rgba(79,70,229,.6)',borderRadius:4},
                {label:'Cost',data:@json($monthly->pluck('cost')),backgroundColor:'rgba(239,68,68,.5)',borderRadius:4},
                {label:'Profit',data:@json($monthly->pluck('profit')),backgroundColor:'rgba(22,163,74,.7)',borderRadius:4},
            ]
        },
        options:{
            responsive:true,maintainAspectRatio:false,
            plugins:{tooltip:{callbacks:{label:ctx=>' Br '+parseFloat(ctx.raw).toLocaleString('en-US',{minimumFractionDigits:2})}}},
            scales:{y:{beginAtZero:true,ticks:{callback:v=>'Br '+v.toLocaleString()}},x:{grid:{display:false}}}
        }
    });
}
</script>
@endpush
