@extends('layouts.app')
@section('title','Sales History')
@section('breadcrumb')
    <li class="breadcrumb-item active">Sales</li>
    <li class="breadcrumb-item active">Sales History</li>
@endsection
@section('content')
@include('partials.page-header',[
    'title'=>'Sales History',
    'subtitle'=>'All sales invoices and transactions',
    'actions'=>[['label'=>'New Sale','route'=>'sales.create','icon'=>'fa-plus','class'=>'btn-primary']],
])

<!-- Summary row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary-soft"><i class="fa fa-receipt"></i></div>
            <div class="stat-body">
                <div class="stat-value">Br {{ number_format($totals->grand_total ?? 0,0) }}</div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success-soft"><i class="fa fa-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value">Br {{ number_format($totals->grand_paid ?? 0,0) }}</div>
                <div class="stat-label">Total Collected</div>
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
            <input type="text" name="search" class="form-control live-search" placeholder="Invoice # or customer…" value="{{ request('search') }}">
        </div>
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
        @if(request()->hasAny(['search','payment_status','date_from','date_to']))
            <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="fa fa-xmark"></i></a>
        @endif
    </div>
</form>
</div>
</div>

<div class="card">
<div class="table-responsive">
<table class="table">
    <thead>
        <tr><th>Invoice</th><th>Customer</th><th>Date</th><th>Items</th><th>Total</th><th>Paid</th><th>Balance</th><th>Payment</th><th>By</th><th class="text-end">Actions</th></tr>
    </thead>
    <tbody>
        @forelse($sales as $sale)
        <tr>
            <td><a href="{{ route('sales.show',$sale) }}" class="fw-semibold text-primary">{{ $sale->invoice_number }}</a></td>
            <td class="text-muted small">{{ $sale->customer_name }}</td>
            <td class="text-muted small">{{ $sale->sale_date->format('M d, Y') }}</td>
            <td class="text-muted">—</td>
            <td class="fw-semibold">Br {{ number_format($sale->total,2) }}</td>
            <td class="text-success">Br {{ number_format($sale->paid_amount,2) }}</td>
            <td class="{{ $sale->balance > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                {{ $sale->balance > 0 ? 'Br '.number_format($sale->balance,2) : '—' }}
            </td>
            <td><span class="badge bg-{{ $sale->payment_status_badge }}">{{ ucfirst($sale->payment_status) }}</span></td>
            <td class="small text-muted">{{ $sale->user->name }}</td>
            <td class="text-end">
                <a href="{{ route('sales.show',$sale) }}" class="btn btn-action btn-outline-secondary me-1" title="View"><i class="fa fa-eye"></i></a>
                <a href="{{ route('sales.invoice',$sale) }}" class="btn btn-action btn-outline-primary me-1" title="Invoice"><i class="fa fa-print"></i></a>
                <a href="{{ route('sales.returns.create',['sale_id'=>$sale->id]) }}" class="btn btn-action btn-outline-warning" title="Return"><i class="fa fa-rotate-left"></i></a>
            </td>
        </tr>
        @empty
        <tr><td colspan="10" class="text-center text-muted py-5">
            <i class="fa fa-receipt fs-2 d-block mb-2 opacity-25"></i>No sales found.
            <a href="{{ route('sales.create') }}">Create first sale.</a>
        </td></tr>
        @endforelse
    </tbody>
</table>
</div>
@if($sales->hasPages())<div class="card-body border-top py-3">{{ $sales->links() }}</div>@endif
</div>
@endsection
