@extends('layouts.app')
@section('title','Profit Report')
@section('breadcrumb')
    <li class="breadcrumb-item active">Reports</li>
    <li class="breadcrumb-item active">Profit</li>
@endsection
@section('content')

@include('partials.report-nav', ['active' => 'profit'])

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0" style="color:#1e293b">Profit &amp; Loss Report</h5>
        <div class="text-muted small">Gross profit analysis by period</div>
    </div>
</div>

<div class="rpt-filter-card">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label">Year</label>
            <select name="year" class="form-select form-select-sm">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">Month (optional)</label>
            <select name="month" class="form-select form-select-sm">
                <option value="">All Year</option>
                @for($m=1;$m<=12;$m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromDate(null,$m,1)->format('F') }}</option>
                @endfor
            </select>
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

{{-- Key metrics highlight --}}
<div class="row g-3 mb-4">
    @php $profitColor = $netProfit >= 0 ? '#10b981' : '#ef4444'; @endphp
    <div class="col-12">
        <div class="card" style="border-left: 4px solid {{ $profitColor }}; background: color-mix(in srgb, {{ $profitColor }} 5%, white)">
        <div class="card-body py-3">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-2">
                <div class="text-muted small mb-1">Revenue</div>
                <div class="fw-bold fs-5" style="color:#6366f1">Br {{ number_format($totalRevenue,0) }}</div>
            </div>
            <div class="col-6 col-md-2">
                <div class="text-muted small mb-1">COGS</div>
                <div class="fw-bold fs-5 text-warning">Br {{ number_format($totalCost,0) }}</div>
            </div>
            <div class="col-6 col-md-2">
                <div class="text-muted small mb-1">Gross Profit</div>
                <div class="fw-bold fs-5 {{ $totalProfit >= 0 ? 'text-success' : 'text-danger' }}">Br {{ number_format($totalProfit,0) }}</div>
            </div>
            <div class="col-6 col-md-2">
                <div class="text-muted small mb-1">Expenses</div>
                <div class="fw-bold fs-5 text-danger">Br {{ number_format($totalExpenses,0) }}</div>
            </div>
            <div class="col-6 col-md-2">
                <div class="text-muted small mb-1" style="font-weight:700">Net Profit</div>
                <div class="fw-bold" style="font-size:1.3rem;color:{{ $profitColor }}">Br {{ number_format($netProfit,0) }}</div>
            </div>
            <div class="col-6 col-md-2">
                <div class="text-muted small mb-1">Gross Margin</div>
                <div class="fw-bold fs-5 {{ $avgMargin >= 0 ? 'text-success' : 'text-danger' }}">{{ $avgMargin }}%</div>
            </div>
        </div>
        </div>
        </div>
    </div>
</div>

@if($monthly->count())
<div class="card mb-4">
<div class="card-header d-flex align-items-center gap-2"><i class="fa fa-chart-bar text-primary"></i><span>Monthly Profit — {{ $year }}</span></div>
<div class="card-body"><div class="chart-container"><canvas id="profitChart"></canvas></div></div>
</div>
@endif

<div class="card mb-4">
<div class="card-header d-flex align-items-center gap-2"><i class="fa fa-calendar text-primary"></i><span>Monthly Breakdown</span></div>
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
                    <div class="progress flex-grow-1" style="height:5px">
                        <div class="progress-bar bg-{{ $row->margin >= 0 ? 'success' : 'danger' }}" style="width:{{ min(100,abs($row->margin)) }}%"></div>
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
        <tr class="table-danger">
            <td colspan="3" class="text-muted">Total Expenses</td>
            <td class="text-danger fw-bold">— Br {{ number_format($totalExpenses,2) }}</td>
            <td></td>
        </tr>
        <tr class="table-{{ $netProfit >= 0 ? 'success' : 'danger' }}">
            <td colspan="3">Net Profit</td>
            <td class="{{ $netProfit >= 0 ? 'text-success' : 'text-danger' }} fw-bold fs-6">Br {{ number_format($netProfit,2) }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>
</div>
</div>

<div class="row g-3">
<div class="col-12 col-md-6">
<div class="card">
<div class="card-header d-flex align-items-center gap-2"><i class="fa fa-trophy text-warning"></i><span>Top Profitable Spare Parts</span></div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Part</th><th>Qty</th><th>Revenue</th><th>Profit</th></tr></thead>
    <tbody>
        @forelse($topParts as $p)
        <tr>
            <td><div class="fw-semibold small">{{ $p->name }}</div><div class="text-muted" style="font-size:.72rem">{{ $p->part_number }}</div></td>
            <td class="text-muted small">{{ $p->qty }}</td>
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
<div class="col-12 col-md-6">
<div class="card">
<div class="card-header d-flex align-items-center gap-2"><i class="fa fa-trophy text-primary"></i><span>Top Profitable Vehicles</span></div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Model</th><th>Qty</th><th>Revenue</th><th>Profit</th></tr></thead>
    <tbody>
        @forelse($topVehicles as $v)
        <tr>
            <td class="fw-semibold small">{{ $v->brand }} {{ $v->model_name }}</td>
            <td class="text-muted small">{{ $v->qty }}</td>
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
                {label:'Revenue', data:@json($monthly->pluck('revenue')), backgroundColor:'rgba(99,102,241,.65)',borderRadius:4},
                {label:'Cost',    data:@json($monthly->pluck('cost')),    backgroundColor:'rgba(239,68,68,.45)',borderRadius:4},
                {label:'Profit',  data:@json($monthly->pluck('profit')),  backgroundColor:'rgba(16,185,129,.7)',borderRadius:4},
            ]
        },
        options:{
            responsive:true,maintainAspectRatio:false,
            plugins:{tooltip:{callbacks:{label:c=>' Br '+parseFloat(c.raw).toLocaleString('en-US',{minimumFractionDigits:2})}}},
            scales:{y:{beginAtZero:true,ticks:{callback:v=>'Br '+v.toLocaleString()},grid:{color:'rgba(0,0,0,.04)'}},x:{grid:{display:false}}}
        }
    });
}
</script>
@endpush
