@extends('layouts.app')
@section('title','Purchase History')
@section('breadcrumb')
    <li class="breadcrumb-item active">Purchases</li>
    <li class="breadcrumb-item active">History</li>
@endsection
@section('content')
@include('partials.page-header',[
    'title'  =>'Purchase History',
    'subtitle'=>'All purchase orders and procurement records',
    'actions'=>[['label'=>'New Purchase','route'=>'purchases.create','icon'=>'fa-plus']],
])

<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary-soft"><i class="fa fa-boxes-stacked"></i></div>
            <div class="stat-body">
                <div class="stat-value">Br {{ number_format($totals->grand_total ?? 0,0) }}</div>
                <div class="stat-label">Total Purchased</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success-soft"><i class="fa fa-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value">Br {{ number_format($totals->grand_paid ?? 0,0) }}</div>
                <div class="stat-label">Total Paid</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-danger-soft"><i class="fa fa-clock"></i></div>
            <div class="stat-body">
                <div class="stat-value">Br {{ number_format($totals->grand_balance ?? 0,0) }}</div>
                <div class="stat-label">Outstanding</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end filter-form">
    <div class="col-12 col-md-3">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fa fa-search"></i></span>
            <input type="text" name="search" class="form-control live-search" placeholder="PO # or supplier…" value="{{ request('search') }}">
        </div>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm ts-select">
            <option value="">All Status</option>
            <option value="received"  {{ request('status')==='received'?'selected':'' }}>Received</option>
            <option value="ordered"   {{ request('status')==='ordered'?'selected':'' }}>Ordered</option>
            <option value="cancelled" {{ request('status')==='cancelled'?'selected':'' }}>Cancelled</option>
        </select>
    </div>
    <div class="col-auto">
        <select name="payment_status" class="form-select form-select-sm ts-select">
            <option value="">Payment</option>
            <option value="paid"    {{ request('payment_status')==='paid'?'selected':'' }}>Paid</option>
            <option value="partial" {{ request('payment_status')==='partial'?'selected':'' }}>Partial</option>
            <option value="unpaid"  {{ request('payment_status')==='unpaid'?'selected':'' }}>Unpaid</option>
        </select>
    </div>
    <div class="col-auto">
        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
    </div>
    <div class="col-auto">
        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-filter me-1"></i>Filter</button>
        @if(request()->hasAny(['search','status','payment_status','date_from','date_to']))
            <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="fa fa-xmark"></i></a>
        @endif
    </div>
</form>
</div>
</div>

<div class="card">
<div class="table-responsive">
<table class="table">
    <thead>
        <tr><th>PO #</th><th>Supplier</th><th>Date</th><th>Due Date</th><th>Total</th><th>Paid</th><th>Balance</th><th>Payment</th><th>Status</th><th class="text-end">Actions</th></tr>
    </thead>
    <tbody>
        @forelse($purchases as $po)
        <tr>
            <td><a href="{{ route('purchases.show',$po) }}" class="fw-semibold text-primary">{{ $po->purchase_number }}</a></td>
            <td class="text-muted small">{{ $po->supplier?->name ?? '—' }}</td>
            <td class="text-muted small">{{ $po->purchase_date->format('M d, Y') }}</td>
            <td class="{{ $po->due_date && $po->due_date->isPast() && $po->payment_status !== 'paid' ? 'text-danger fw-semibold' : 'text-muted' }} small">
                {{ $po->due_date ? $po->due_date->format('M d, Y') : '—' }}
            </td>
            <td class="fw-semibold">Br {{ number_format($po->total,2) }}</td>
            <td class="text-success">Br {{ number_format($po->paid_amount,2) }}</td>
            <td class="{{ $po->balance > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                {{ $po->balance > 0 ? 'Br '.number_format($po->balance,2) : '—' }}
            </td>
            <td><span class="badge bg-{{ $po->payment_status_badge }}">{{ ucfirst($po->payment_status) }}</span></td>
            <td><span class="badge bg-{{ $po->status_badge }}">{{ ucfirst($po->status) }}</span></td>
            <td class="text-end">
                <a href="{{ route('purchases.show',$po) }}" class="btn btn-action btn-outline-secondary me-1"><i class="fa fa-eye"></i></a>
                @if($po->status !== 'received')
                <form method="POST" action="{{ route('purchases.receive',$po) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-action btn-outline-success me-1" title="Mark Received"
                            onclick="return confirm('Mark this purchase as received and update stock?')">
                        <i class="fa fa-check"></i>
                    </button>
                </form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="10" class="text-center text-muted py-5">
            <i class="fa fa-boxes-stacked fs-2 d-block mb-2 opacity-25"></i>No purchases found.
            <a href="{{ route('purchases.create') }}">Create first purchase.</a>
        </td></tr>
        @endforelse
    </tbody>
</table>
</div>
@if($purchases->hasPages())<div class="card-body border-top py-3">{{ $purchases->links() }}</div>@endif
</div>
@endsection
