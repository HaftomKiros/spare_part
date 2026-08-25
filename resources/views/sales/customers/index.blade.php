@extends('layouts.app')
@section('title','Customers')
@section('breadcrumb')
    <li class="breadcrumb-item active">Sales</li>
    <li class="breadcrumb-item active">Customers</li>
@endsection
@section('content')
@include('partials.page-header',[
    'title'=>'Customers',
    'subtitle'=>'Manage your customer directory',
    'actions'=>[['label'=>'Add Customer','route'=>'sales.customers.create','icon'=>'fa-plus']],
])

<div class="card mb-3">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end filter-form">
    <div class="col-12 col-md-4">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fa fa-search"></i></span>
            <input type="text" name="search" class="form-control live-search" placeholder="Name, phone, code…" value="{{ request('search') }}">
        </div>
    </div>
    <div class="col-auto">
        <select name="type" class="form-select form-select-sm ts-select">
            <option value="">All Types</option>
            <option value="individual" {{ request('type')==='individual'?'selected':'' }}>Individual</option>
            <option value="business"   {{ request('type')==='business'?'selected':'' }}>Business</option>
        </select>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm ts-select">
            <option value="">All Status</option>
            <option value="active"   {{ request('status')==='active'?'selected':'' }}>Active</option>
            <option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Inactive</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-filter me-1"></i>Filter</button>
        @if(request()->hasAny(['search','type','status']))
            <a href="{{ route('sales.customers.index') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="fa fa-xmark"></i></a>
        @endif
    </div>
</form>
</div>
</div>

<div class="card">
<div class="table-responsive">
<table class="table">
    <thead>
        <tr><th>#</th><th>Customer</th><th>Type</th><th>Phone</th><th>City</th><th>Sales</th><th>Balance</th><th>Status</th><th class="text-end">Actions</th></tr>
    </thead>
    <tbody>
        @forelse($customers as $c)
        <tr>
            <td class="text-muted small">{{ $c->customer_code }}</td>
            <td>
                <a href="{{ route('sales.customers.show',$c) }}" class="fw-semibold text-dark text-decoration-none">{{ $c->name }}</a>
                @if($c->email)<div class="text-muted small">{{ $c->email }}</div>@endif
            </td>
            <td><span class="badge bg-{{ $c->customer_type==='business'?'primary':'secondary' }} bg-opacity-15 text-{{ $c->customer_type==='business'?'primary':'secondary' }}">{{ ucfirst($c->customer_type) }}</span></td>
            <td class="text-muted">{{ $c->phone }}</td>
            <td class="text-muted">{{ $c->city ?? '—' }}</td>
            <td><a href="{{ route('sales.index',['search'=>$c->name]) }}" class="text-primary">{{ $c->sales_count }}</a></td>
            <td class="{{ $c->balance > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                {{ $c->balance > 0 ? 'Br '.number_format($c->balance,2) : '—' }}
            </td>
            <td><span class="badge badge-status-{{ $c->status }}">{{ ucfirst($c->status) }}</span></td>
            <td class="text-end">
                <a href="{{ route('sales.customers.show',$c) }}" class="btn btn-action btn-outline-secondary me-1"><i class="fa fa-eye"></i></a>
                <a href="{{ route('sales.customers.edit',$c) }}" class="btn btn-action btn-outline-primary me-1"><i class="fa fa-pen"></i></a>
                <button class="btn btn-action btn-outline-danger"
                    data-delete-url="{{ route('sales.customers.destroy',$c) }}"
                    data-delete-message="Delete customer '{{ $c->name }}'?"><i class="fa fa-trash"></i></button>
            </td>
        </tr>
        @empty
        <tr><td colspan="9" class="text-center text-muted py-5">
            <i class="fa fa-users fs-2 d-block mb-2 opacity-25"></i>No customers found.
            <a href="{{ route('sales.customers.create') }}">Add one now.</a>
        </td></tr>
        @endforelse
    </tbody>
</table>
</div>
@if($customers->hasPages())<div class="card-body border-top py-3">{{ $customers->links() }}</div>@endif
</div>
@include('partials.delete-modal')
@endsection
