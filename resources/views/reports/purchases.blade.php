@extends('layouts.app')
@section('title','Purchases Report')
@section('breadcrumb')
    <li class="breadcrumb-item active">Reports</li>
    <li class="breadcrumb-item active">Purchases</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'Purchases Report','subtitle'=>'Procurement analysis for the selected period'])

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
</form>
</div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon bg-primary-soft"><i class="fa fa-file-invoice"></i></div><div class="stat-body"><div class="stat-value">{{ $summary->total_orders }}</div><div class="stat-label">Orders</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon bg-warning-soft"><i class="fa fa-boxes-stacked"></i></div><div class="stat-body"><div class="stat-value">Br {{ number_format($summary->total_amount,0) }}</div><div class="stat-label">Total Purchased</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon bg-success-soft"><i class="fa fa-circle-check"></i></div><div class="stat-body"><div class="stat-value">Br {{ number_format($summary->total_paid,0) }}</div><div class="stat-label">Total Paid</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon bg-danger-soft"><i class="fa fa-clock"></i></div><div class="stat-body"><div class="stat-value">Br {{ number_format($summary->total_balance,0) }}</div><div class="stat-label">Outstanding</div></div></div></div>
</div>

<div class="row g-3 mb-4">
<div class="col-12 col-md-5">
<div class="card">
<div class="card-header"><i class="fa fa-truck me-2 text-primary"></i>Top Suppliers</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Supplier</th><th>Orders</th><th>Total</th></tr></thead>
    <tbody>
        @forelse($bySupplier as $s)
        <tr>
            <td class="fw-semibold small">{{ $s->name }}</td>
            <td class="text-muted">{{ $s->orders }}</td>
            <td>Br {{ number_format($s->total,2) }}</td>
        </tr>
        @empty
        <tr><td colspan="3" class="text-center text-muted py-3">No data.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</div>
</div>

<div class="col-12 col-md-7">
<div class="card">
<div class="card-header"><i class="fa fa-list me-2 text-primary"></i>Purchase Orders</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>PO #</th><th>Supplier</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
    <tbody>
        @forelse($purchases as $po)
        <tr>
            <td><a href="{{ route('purchases.show',$po) }}" class="text-primary fw-medium">{{ $po->purchase_number }}</a></td>
            <td class="text-muted small">{{ $po->supplier->name }}</td>
            <td class="text-muted small">{{ $po->purchase_date->format('M d, Y') }}</td>
            <td class="fw-semibold">Br {{ number_format($po->total,2) }}</td>
            <td><span class="badge bg-{{ $po->payment_status_badge }}">{{ ucfirst($po->payment_status) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center text-muted py-3">No purchases in this period.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
@if($purchases->hasPages())<div class="card-body border-top py-2">{{ $purchases->links() }}</div>@endif
</div>
</div>
</div>
@endsection
