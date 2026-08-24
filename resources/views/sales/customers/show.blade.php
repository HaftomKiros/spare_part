@extends('layouts.app')
@section('title', $customer->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('sales.customers.index') }}">Customers</a></li>
    <li class="breadcrumb-item active">{{ $customer->name }}</li>
@endsection
@section('content')
@include('partials.page-header',[
    'title'=>$customer->name,
    'subtitle'=>$customer->customer_code.' · '.ucfirst($customer->customer_type),
    'actions'=>[['label'=>'Edit','route'=>'sales.customers.edit','icon'=>'fa-pen','class'=>'btn-outline-primary']],
])

<div class="row g-3">
<div class="col-12 col-md-4">
<div class="card">
<div class="card-header"><i class="fa fa-info-circle me-2 text-primary"></i>Details</div>
<div class="card-body">
<table class="table table-sm table-borderless mb-0 small">
    <tr><th class="text-muted fw-normal">Code</th><td>{{ $customer->customer_code }}</td></tr>
    <tr><th class="text-muted fw-normal">Type</th><td>{{ ucfirst($customer->customer_type) }}</td></tr>
    <tr><th class="text-muted fw-normal">Phone</th><td>{{ $customer->phone }}</td></tr>
    <tr><th class="text-muted fw-normal">Email</th><td>{{ $customer->email ?? '—' }}</td></tr>
    <tr><th class="text-muted fw-normal">City</th><td>{{ $customer->city ?? '—' }}</td></tr>
    <tr><th class="text-muted fw-normal">Address</th><td>{{ $customer->address ?? '—' }}</td></tr>
    <tr><th class="text-muted fw-normal">Status</th><td><span class="badge badge-status-{{ $customer->status }}">{{ ucfirst($customer->status) }}</span></td></tr>
</table>
</div>
</div>
</div>

<div class="col-12 col-md-8">
<div class="row g-3 mb-3">
    <div class="col-4">
        <div class="card text-center p-3">
            <div class="fs-3 fw-bold text-primary">{{ $customer->sales_count }}</div>
            <div class="small text-muted">Total Sales</div>
        </div>
    </div>
    <div class="col-4">
        <div class="card text-center p-3">
            <div class="fs-3 fw-bold text-success">Br {{ number_format($totalSpent,2) }}</div>
            <div class="small text-muted">Total Spent</div>
        </div>
    </div>
    <div class="col-4">
        <div class="card text-center p-3">
            <div class="fs-3 fw-bold {{ $customer->balance > 0 ? 'text-danger' : 'text-muted' }}">Br {{ number_format($customer->balance,2) }}</div>
            <div class="small text-muted">Outstanding</div>
        </div>
    </div>
</div>

<div class="card">
<div class="card-header"><i class="fa fa-receipt me-2 text-primary"></i>Recent Sales</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Invoice</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead>
    <tbody>
        @forelse($recentSales as $s)
        <tr>
            <td><a href="{{ route('sales.show',$s) }}" class="text-primary fw-medium">{{ $s->invoice_number }}</a></td>
            <td class="text-muted small">{{ $s->sale_date->format('M d, Y') }}</td>
            <td class="fw-semibold">Br {{ number_format($s->total,2) }}</td>
            <td><span class="badge bg-{{ $s->payment_status_badge }}">{{ ucfirst($s->payment_status) }}</span></td>
            <td><span class="badge bg-{{ $s->status_badge }}">{{ ucfirst($s->status) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center text-muted py-3">No sales yet.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</div>
</div>
</div>
@endsection
