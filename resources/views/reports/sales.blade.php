@extends('layouts.app')
@section('title','Sales Report')
@section('breadcrumb')
    <li class="breadcrumb-item active">Reports</li>
    <li class="breadcrumb-item active">Sales</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'Sales Report','subtitle'=>'Revenue analysis for the selected period'])

<!-- Date Filter -->
<div class="card mb-4">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-auto"><label class="form-label small mb-1">From</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
    </div>
    <div class="col-auto"><label class="form-label small mb-1">To</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary mt-3"><i class="fa fa-filter me-1"></i>Apply</button></div>
    <div class="col-auto ms-auto text-muted small mt-3">
        Showing: <strong>{{ \Carbon\Carbon::parse($dateFrom)->format('M d') }}</strong> — <strong>{{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</strong>
    </div>
</form>
</div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-icon bg-primary-soft"><i class="fa fa-receipt"></i></div>
            <div class="stat-body"><div class="stat-value">{{ $summary->total_invoices }}</div><div class="stat-label">Invoices</div></div>
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
</div>

<!-- Chart -->
<div class="card mb-4">
<div class="card-header"><i class="fa fa-chart-bar me-2 text-primary"></i>Daily Sales</div>
<div class="card-body"><div class="chart-container"><canvas id="dailyChart"></canvas></div></div>
</div>

<!-- Table -->
<div class="card">
<div class="card-header"><i class="fa fa-list me-2 text-primary"></i>Sales List</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th>Total</th><th>Paid</th><th>Balance</th><th>Payment</th><th>By</th></tr></thead>
    <tbody>
        @forelse($sales as $s)
        <tr>
            <td><a href="{{ route('sales.show',$s) }}" class="text-primary fw-medium">{{ $s->invoice_number }}</a></td>
            <td class="text-muted">{{ $s->customer_name }}</td>
            <td class="text-muted small">{{ $s->sale_date->format('M d, Y') }}</td>
            <td class="fw-semibold">Br {{ number_format($s->total,2) }}</td>
            <td class="text-success">Br {{ number_format($s->paid_amount,2) }}</td>
            <td class="{{ $s->balance > 0 ? 'text-danger' : 'text-muted' }}">{{ $s->balance > 0 ? 'Br '.number_format($s->balance,2) : '—' }}</td>
            <td><span class="badge bg-{{ $s->payment_status_badge }}">{{ ucfirst($s->payment_status) }}</span></td>
            <td class="small text-muted">{{ $s->user->name }}</td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center text-muted py-4">No sales in this period.</td></tr>
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
            datasets:[{label:'Sales (Br)',data:@json($daily->pluck('total')),backgroundColor:'rgba(79,70,229,.7)',borderRadius:6}]
        },
        options:{
            responsive:true,maintainAspectRatio:false,
            plugins:{legend:{display:false},tooltip:{callbacks:{label:ctx=>'Br '+parseFloat(ctx.raw).toLocaleString('en-US',{minimumFractionDigits:2})}}},
            scales:{y:{beginAtZero:true,ticks:{callback:v=>'Br '+v.toLocaleString()}},x:{grid:{display:false}}}
        }
    });
}
</script>
@endpush
