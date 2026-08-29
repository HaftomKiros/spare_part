@extends('layouts.app')
@section('title','Purchases Report')
@section('breadcrumb')
    <li class="breadcrumb-item active">Reports</li>
    <li class="breadcrumb-item active">Purchases</li>
@endsection
@section('content')

@include('partials.report-nav', ['active' => 'purchases'])

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0" style="color:#1e293b">Purchases Report</h5>
        <div class="text-muted small">Procurement analysis for the selected period</div>
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
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon bg-primary-soft"><i class="fa fa-file-invoice"></i></div><div class="stat-body"><div class="stat-value">{{ number_format($summary->total_orders) }}</div><div class="stat-label">Orders</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon bg-warning-soft"><i class="fa fa-boxes-stacked"></i></div><div class="stat-body"><div class="stat-value">Br {{ number_format($summary->total_amount,0) }}</div><div class="stat-label">Total Purchased</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon bg-success-soft"><i class="fa fa-circle-check"></i></div><div class="stat-body"><div class="stat-value">Br {{ number_format($summary->total_paid,0) }}</div><div class="stat-label">Total Paid</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon bg-danger-soft"><i class="fa fa-clock"></i></div><div class="stat-body"><div class="stat-value">Br {{ number_format($summary->total_balance,0) }}</div><div class="stat-label">Outstanding</div></div></div></div>
</div>

{{-- Item type breakdown --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning-soft"><i class="fa fa-motorcycle"></i></div>
            <div class="stat-body">
                <div class="stat-value">Br {{ number_format($itemBreakdown['vehicle']->total ?? 0, 0) }}</div>
                <div class="stat-label">Vehicles <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:.65rem">{{ number_format($itemBreakdown['vehicle']->qty ?? 0) }} units</span></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-info-soft"><i class="fa fa-gears"></i></div>
            <div class="stat-body">
                <div class="stat-value">Br {{ number_format($itemBreakdown['spare_part']->total ?? 0, 0) }}</div>
                <div class="stat-label">Spare Parts <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:.65rem">{{ number_format($itemBreakdown['spare_part']->qty ?? 0) }} units</span></div>
            </div>
        </div>
    </div>
</div>

{{-- Daily purchases chart --}}
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="fa fa-chart-bar text-primary"></i><span>Daily Purchases</span>
        <span class="badge bg-primary-subtle text-primary-emphasis ms-auto" style="font-size:.72rem">{{ count($chartLabels) }} days</span>
    </div>
    <div class="card-body"><div class="chart-container"><canvas id="purchaseChart"></canvas></div></div>
</div>

<div class="row g-3">
<div class="col-12 col-md-4">
<div class="card h-100">
<div class="card-header d-flex align-items-center gap-2"><i class="fa fa-truck text-primary"></i><span>Top Suppliers</span></div>
<div class="table-responsive">
<table class="table mb-0">
    <thead><tr><th>Supplier</th><th>Orders</th><th>Total</th></tr></thead>
    <tbody>
        @forelse($bySupplier as $s)
        <tr>
            <td class="fw-semibold small">{{ $s->name }}</td>
            <td class="text-muted small">{{ $s->orders }}</td>
            <td class="fw-semibold" style="color:var(--brand-1)">Br {{ number_format($s->total,2) }}</td>
        </tr>
        @empty
        <tr><td colspan="3" class="text-center text-muted py-3">No data.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</div>
</div>

<div class="col-12 col-md-8">
<div class="card">
<div class="card-header d-flex align-items-center gap-2">
    <i class="fa fa-list text-primary"></i><span>Purchase Orders</span>
    <span class="badge bg-secondary-subtle text-secondary ms-auto" style="font-size:.72rem">{{ $purchases->total() }} records</span>
</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>PO #</th><th>Supplier</th><th>Date</th><th>Total</th><th class="d-none d-lg-table-cell">Warehouse</th><th>Status</th></tr></thead>
    <tbody>
        @forelse($purchases as $po)
        <tr>
            <td><a href="{{ route('purchases.show',$po) }}" class="text-primary fw-semibold">{{ $po->purchase_number }}</a></td>
            <td class="text-muted small">{{ $po->supplier?->name ?? '—' }}</td>
            <td class="text-muted small">{{ $po->purchase_date->format('M d, Y') }}</td>
            <td class="fw-semibold">Br {{ number_format($po->total,2) }}</td>
            <td class="text-muted small d-none d-lg-table-cell">{{ $po->warehouse?->name ?? '—' }}</td>
            <td><span class="badge bg-{{ $po->payment_status_badge }}">{{ ucfirst($po->payment_status) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted py-4">No purchases in this period.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
@if($purchases->hasPages())<div class="card-body border-top py-2">{{ $purchases->links() }}</div>@endif
</div>
</div>
</div>
@endsection
@push('scripts')
<script>
const pCtx = document.getElementById('purchaseChart');
if (pCtx) {
    new Chart(pCtx, {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [
                {
                    label: 'Vehicles (Br)',
                    data: @json($chartVehicles),
                    backgroundColor: 'rgba(249,115,22,.75)',
                    borderRadius: 5,
                    borderSkipped: false,
                    stack: 'purchase'
                },
                {
                    label: 'Spare Parts (Br)',
                    data: @json($chartSpareParts),
                    backgroundColor: 'rgba(14,165,233,.75)',
                    borderRadius: 5,
                    borderSkipped: false,
                    stack: 'purchase'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: 'top' },
                tooltip: { callbacks: { label: c => c.dataset.label + ': Br ' + parseFloat(c.raw).toLocaleString('en-US', { minimumFractionDigits: 2 }) } }
            },
            scales: {
                x: { stacked: true, grid: { display: false } },
                y: { stacked: true, beginAtZero: true, ticks: { callback: v => 'Br ' + v.toLocaleString() }, grid: { color: 'rgba(0,0,0,.04)' } }
            }
        }
    });
}
</script>
@endpush
