@extends('layouts.app')
@section('title','Sales Report')
@section('breadcrumb')
    <li class="breadcrumb-item active">Reports</li>
    <li class="breadcrumb-item active">Sales</li>
@endsection
@section('content')

@include('partials.report-nav', ['active' => 'sales'])

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0" style="color:#1e293b">Sales Report</h5>
        <div class="text-muted small">Revenue analysis for the selected period</div>
    </div>
    @if($warehouseId)
    <span class="rpt-period-badge"><i class="fa fa-warehouse"></i>{{ $warehouses->find($warehouseId)?->name }}</span>
    @endif
</div>

<div class="rpt-filter-card d-flex flex-wrap gap-3 align-items-end">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end w-100">
        <div>
            <label class="form-label">From</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
        </div>
        <div>
            <label class="form-label">To</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
        </div>
        @include('partials.warehouse-filter')
        <div class="mt-auto">
            <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-filter me-1"></i>Apply</button>
        </div>
        <div class="mt-auto ms-auto">
            <span class="rpt-period-badge">
                <i class="fa fa-calendar-days"></i>
                {{ \Carbon\Carbon::parse($dateFrom)->format('M d') }} — {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
            </span>
        </div>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-icon bg-primary-soft"><i class="fa fa-receipt"></i></div>
            <div class="stat-body"><div class="stat-value">{{ number_format($summary->total_invoices) }}</div><div class="stat-label">Invoices</div></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-icon bg-success-soft"><i class="fa fa-chart-line"></i></div>
            <div class="stat-body"><div class="stat-value">Br {{ number_format($summary->gross_revenue,0) }}</div><div class="stat-label">Revenue</div></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-icon bg-warning-soft"><i class="fa fa-tag"></i></div>
            <div class="stat-body"><div class="stat-value">Br {{ number_format($summary->total_discounts,0) }}</div><div class="stat-label">Discounts</div></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-icon bg-info-soft"><i class="fa fa-percent"></i></div>
            <div class="stat-body"><div class="stat-value">Br {{ number_format($summary->total_tax,0) }}</div><div class="stat-label">Tax</div></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-icon bg-success-soft"><i class="fa fa-circle-check"></i></div>
            <div class="stat-body"><div class="stat-value">Br {{ number_format($summary->total_collected,0) }}</div><div class="stat-label">Collected</div></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-icon bg-danger-soft"><i class="fa fa-clock"></i></div>
            <div class="stat-body"><div class="stat-value">Br {{ number_format($summary->total_outstanding,0) }}</div><div class="stat-label">Outstanding</div></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-icon bg-success-soft"><i class="fa fa-sack-dollar"></i></div>
            <div class="stat-body">
                <div class="stat-value {{ $totalProfit < 0 ? 'text-danger' : 'text-success' }}">Br {{ number_format($totalProfit,0) }}</div>
                <div class="stat-label">Net Sales Profit</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
<div class="card-header d-flex align-items-center gap-2">
    <i class="fa fa-chart-bar text-primary"></i><span>Daily Sales</span>
    <span class="badge bg-primary-subtle text-primary-emphasis ms-auto" style="font-size:.72rem">{{ $daily->count() }} days</span>
</div>
<div class="card-body"><div class="chart-container"><canvas id="dailyChart"></canvas></div></div>
</div>

<div class="card">
<div class="card-header d-flex align-items-center gap-2">
    <i class="fa fa-list text-primary"></i><span>Sales List</span>
    <span class="badge bg-secondary-subtle text-secondary ms-auto" style="font-size:.72rem">{{ $sales->total() }} records</span>
</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th>Total</th><th>Paid</th><th>Balance</th><th>Payment</th><th class="d-none d-lg-table-cell">Warehouse</th><th>By</th></tr></thead>
    <tbody>
        @forelse($sales as $s)
        <tr>
            <td><a href="{{ route('sales.show',$s) }}" class="text-primary fw-semibold">{{ $s->invoice_number }}</a></td>
            <td class="text-muted small">{{ $s->customer_name ?? 'Walk-in' }}</td>
            <td class="text-muted small">{{ $s->sale_date->format('M d, Y') }}</td>
            <td class="fw-semibold">Br {{ number_format($s->total,2) }}</td>
            <td class="text-success small">Br {{ number_format($s->paid_amount,2) }}</td>
            <td class="{{ $s->balance > 0 ? 'text-danger fw-semibold' : 'text-muted' }} small">{{ $s->balance > 0 ? 'Br '.number_format($s->balance,2) : '—' }}</td>
            <td><span class="badge bg-{{ $s->payment_status_badge }}">{{ ucfirst($s->payment_status) }}</span></td>
            <td class="small text-muted d-none d-lg-table-cell">{{ $s->warehouse?->name ?? '—' }}</td>
            <td class="small text-muted">{{ $s->user->name }}</td>
        </tr>
        @empty
        <tr><td colspan="9" class="text-center text-muted py-5">
            <i class="fa fa-chart-line fs-2 d-block mb-2 opacity-25"></i>No sales in this period.
        </td></tr>
        @endforelse
    </tbody>
</table>
</div>
@if($sales->hasPages())<div class="card-body border-top py-3">{{ $sales->links() }}</div>@endif
</div>
@endsection
@push('scripts')
<script>
const ctx = document.getElementById('dailyChart');
if(ctx) {
    new Chart(ctx,{
        type:'bar',
        data:{
            labels: @json($daily->pluck('date')->map(fn($d)=>\Carbon\Carbon::parse($d)->format('M d'))),
            datasets:[
                {
                    label:'Revenue (Br)',
                    data:@json($daily->pluck('total')),
                    backgroundColor:'rgba(99,102,241,.75)',
                    borderRadius:6,
                    borderSkipped:false,
                    order:2
                },
                {
                    label:'Net Profit (Br)',
                    data:@json($daily->pluck('profit')),
                    type:'line',
                    borderColor:'rgba(16,185,129,1)',
                    backgroundColor:'rgba(16,185,129,.15)',
                    pointBackgroundColor:'rgba(16,185,129,1)',
                    pointRadius:4,
                    fill:true,
                    tension:0.3,
                    order:1
                }
            ]
        },
        options:{
            responsive:true,maintainAspectRatio:false,
            plugins:{
                legend:{display:true,position:'top'},
                tooltip:{callbacks:{label:c=>'Br '+parseFloat(c.raw).toLocaleString('en-US',{minimumFractionDigits:2})}}
            },
            scales:{y:{beginAtZero:true,ticks:{callback:v=>'Br '+v.toLocaleString()},grid:{color:'rgba(0,0,0,.04)'}},x:{grid:{display:false}}}
        }
    });
}
</script>
@endpush
