@extends('layouts.app')
@section('title',$supplier->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('purchases.suppliers.index') }}">Suppliers</a></li>
    <li class="breadcrumb-item active">{{ $supplier->name }}</li>
@endsection
@section('content')
@include('partials.page-header',[
    'title'  =>$supplier->name,
    'subtitle'=>$supplier->supplier_code,
    'actions'=>[['label'=>'Edit','route'=>'purchases.suppliers.edit','icon'=>'fa-pen','class'=>'btn-outline-primary']],
])

<div class="row g-3">
<div class="col-12 col-md-4">
<div class="card">
<div class="card-header"><i class="fa fa-info-circle me-2 text-primary"></i>Supplier Details</div>
<div class="card-body">
<table class="table table-sm table-borderless mb-0 small">
    <tr><th class="text-muted fw-normal">Code</th><td>{{ $supplier->supplier_code }}</td></tr>
    <tr><th class="text-muted fw-normal">Company</th><td>{{ $supplier->company ?? '—' }}</td></tr>
    <tr><th class="text-muted fw-normal">Contact</th><td>{{ $supplier->contact_person ?? '—' }}</td></tr>
    <tr><th class="text-muted fw-normal">Phone</th><td>{{ $supplier->phone }}</td></tr>
    <tr><th class="text-muted fw-normal">Email</th><td>{{ $supplier->email ?? '—' }}</td></tr>
    <tr><th class="text-muted fw-normal">City</th><td>{{ $supplier->city ?? '—' }}</td></tr>
    <tr><th class="text-muted fw-normal">Address</th><td>{{ $supplier->address ?? '—' }}</td></tr>
    <tr><th class="text-muted fw-normal">Status</th><td><span class="badge badge-status-{{ $supplier->status }}">{{ ucfirst($supplier->status) }}</span></td></tr>
</table>
</div>
</div>
</div>

<div class="col-12 col-md-8">
<div class="row g-3 mb-3">
    <div class="col-4">
        <div class="card text-center p-3">
            <div class="fs-3 fw-bold text-primary">{{ $supplier->purchases_count }}</div>
            <div class="small text-muted">Total Orders</div>
        </div>
    </div>
    <div class="col-4">
        <div class="card text-center p-3">
            <div class="fs-3 fw-bold text-success">Br {{ number_format($totalPurchased,2) }}</div>
            <div class="small text-muted">Total Purchased</div>
        </div>
    </div>
    <div class="col-4">
        <div class="card text-center p-3">
            <div class="fs-3 fw-bold {{ $supplier->balance > 0 ? 'text-danger' : 'text-muted' }}">
                Br {{ number_format($supplier->balance,2) }}
            </div>
            <div class="small text-muted">Outstanding</div>
        </div>
    </div>
</div>

<div class="card">
<div class="card-header"><i class="fa fa-boxes-stacked me-2 text-primary"></i>Recent Purchases</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>PO #</th><th>Date</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
    <tbody>
        @forelse($recentPurchases as $p)
        <tr>
            <td><a href="{{ route('purchases.show',$p) }}" class="text-primary fw-medium">{{ $p->purchase_number }}</a></td>
            <td class="text-muted small">{{ $p->purchase_date->format('M d, Y') }}</td>
            <td class="fw-semibold">Br {{ number_format($p->total,2) }}</td>
            <td class="text-success">Br {{ number_format($p->paid_amount,2) }}</td>
            <td class="{{ $p->balance > 0 ? 'text-danger' : 'text-muted' }}">
                {{ $p->balance > 0 ? 'Br '.number_format($p->balance,2) : '—' }}
            </td>
            <td><span class="badge bg-{{ $p->status_badge }}">{{ ucfirst($p->status) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted py-3">No purchases yet.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</div>
</div>
</div>
@endsection
