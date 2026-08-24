@extends('layouts.app')
@section('title','Sales Returns')
@section('breadcrumb')
    <li class="breadcrumb-item active">Sales</li>
    <li class="breadcrumb-item active">Returns</li>
@endsection
@section('content')
@include('partials.page-header',[
    'title'=>'Sales Returns',
    'subtitle'=>'Track returned merchandise and refunds',
    'actions'=>[['label'=>'New Return','route'=>'sales.returns.create','icon'=>'fa-plus','class'=>'btn-warning']],
])

<div class="card mb-3">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-12 col-md-4">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fa fa-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Return #, invoice #, customer…" value="{{ request('search') }}">
        </div>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm">
            <option value="">All Status</option>
            <option value="approved" {{ request('status')==='approved'?'selected':'' }}>Approved</option>
            <option value="pending"  {{ request('status')==='pending'?'selected':'' }}>Pending</option>
            <option value="rejected" {{ request('status')==='rejected'?'selected':'' }}>Rejected</option>
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
        @if(request()->hasAny(['search','status','date_from','date_to']))
            <a href="{{ route('sales.returns.index') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="fa fa-xmark"></i></a>
        @endif
    </div>
</form>
</div>
</div>

<div class="card">
<div class="table-responsive">
<table class="table">
    <thead>
        <tr><th>Return #</th><th>Invoice</th><th>Customer</th><th>Date</th><th>Type</th><th>Amount</th><th>Status</th><th>By</th><th class="text-end">View</th></tr>
    </thead>
    <tbody>
        @forelse($returns as $ret)
        <tr>
            <td class="fw-semibold text-primary">{{ $ret->return_number }}</td>
            <td><a href="{{ route('sales.show',$ret->sale) }}" class="text-muted">{{ $ret->sale->invoice_number }}</a></td>
            <td>{{ $ret->customer?->name ?? 'Walk-in' }}</td>
            <td class="text-muted small">{{ $ret->return_date->format('M d, Y') }}</td>
            <td><span class="badge bg-warning text-dark">{{ ucfirst($ret->return_type) }}</span></td>
            <td class="fw-semibold text-danger">Br {{ number_format($ret->total_amount,2) }}</td>
            <td><span class="badge bg-{{ $ret->status_badge }}">{{ ucfirst($ret->status) }}</span></td>
            <td class="small text-muted">{{ $ret->user->name }}</td>
            <td class="text-end">
                <a href="{{ route('sales.returns.show',$ret) }}" class="btn btn-action btn-outline-primary"><i class="fa fa-eye"></i></a>
            </td>
        </tr>
        @empty
        <tr><td colspan="9" class="text-center text-muted py-5">
            <i class="fa fa-rotate-left fs-2 d-block mb-2 opacity-25"></i>No returns recorded.
        </td></tr>
        @endforelse
    </tbody>
</table>
</div>
@if($returns->hasPages())<div class="card-body border-top py-3">{{ $returns->links() }}</div>@endif
</div>
@endsection
