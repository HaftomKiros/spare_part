@extends('layouts.app')
@section('title','Suppliers')
@section('breadcrumb')
    <li class="breadcrumb-item active">Purchases</li>
    <li class="breadcrumb-item active">Suppliers</li>
@endsection
@section('content')
@include('partials.page-header',[
    'title'   =>'Suppliers',
    'subtitle'=>'Manage your supplier / vendor directory',
    'actions' =>[['label'=>'Add Supplier','route'=>'purchases.suppliers.create','icon'=>'fa-plus']],
])

<div class="card mb-3">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-12 col-md-5">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fa fa-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Name, company, code, phone…" value="{{ request('search') }}">
        </div>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm">
            <option value="">All Status</option>
            <option value="active"   {{ request('status')==='active'?'selected':'' }}>Active</option>
            <option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Inactive</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-filter me-1"></i>Filter</button>
        @if(request()->hasAny(['search','status']))
            <a href="{{ route('purchases.suppliers.index') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="fa fa-xmark"></i></a>
        @endif
    </div>
</form>
</div>
</div>

<div class="card">
<div class="table-responsive">
<table class="table">
    <thead>
        <tr><th>Code</th><th>Supplier</th><th>Phone</th><th>City</th><th>Contact</th><th>Purchases</th><th>Balance</th><th>Status</th><th class="text-end">Actions</th></tr>
    </thead>
    <tbody>
        @forelse($suppliers as $s)
        <tr>
            <td class="text-muted small">{{ $s->supplier_code }}</td>
            <td>
                <a href="{{ route('purchases.suppliers.show',$s) }}" class="fw-semibold text-dark text-decoration-none">{{ $s->name }}</a>
                @if($s->company)<div class="text-muted small">{{ $s->company }}</div>@endif
            </td>
            <td class="text-muted">{{ $s->phone }}</td>
            <td class="text-muted">{{ $s->city ?? '—' }}</td>
            <td class="text-muted small">{{ $s->contact_person ?? '—' }}</td>
            <td>
                <a href="{{ route('purchases.index',['search'=>$s->name]) }}" class="text-primary">{{ $s->purchases_count }}</a>
            </td>
            <td class="{{ $s->balance > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                {{ $s->balance > 0 ? 'Br '.number_format($s->balance,2) : '—' }}
            </td>
            <td><span class="badge badge-status-{{ $s->status }}">{{ ucfirst($s->status) }}</span></td>
            <td class="text-end">
                <a href="{{ route('purchases.suppliers.show',$s) }}" class="btn btn-action btn-outline-secondary me-1"><i class="fa fa-eye"></i></a>
                <a href="{{ route('purchases.suppliers.edit',$s) }}" class="btn btn-action btn-outline-primary me-1"><i class="fa fa-pen"></i></a>
                <button class="btn btn-action btn-outline-danger"
                    data-delete-url="{{ route('purchases.suppliers.destroy',$s) }}"
                    data-delete-message="Delete supplier '{{ $s->name }}'?"><i class="fa fa-trash"></i></button>
            </td>
        </tr>
        @empty
        <tr><td colspan="9" class="text-center text-muted py-5">
            <i class="fa fa-truck fs-2 d-block mb-2 opacity-25"></i>No suppliers found.
            <a href="{{ route('purchases.suppliers.create') }}">Add one now.</a>
        </td></tr>
        @endforelse
    </tbody>
</table>
</div>
@if($suppliers->hasPages())<div class="card-body border-top py-3">{{ $suppliers->links() }}</div>@endif
</div>
@include('partials.delete-modal')
@endsection
