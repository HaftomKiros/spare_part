@extends('layouts.app')
@section('title','Expenses')
@section('breadcrumb')
    <li class="breadcrumb-item active">Expenses</li>
@endsection
@section('content')
@include('partials.page-header',[
    'title'   => 'Expenses',
    'subtitle'=> 'All recorded business expenses',
    'actions' => [
        ['label'=>'New Expense','route'=>'expenses.create','icon'=>'fa-plus','class'=>'btn-primary'],
        ['label'=>'Categories', 'route'=>'expense-categories.index','icon'=>'fa-tags','class'=>'btn-outline-secondary'],
    ],
])

{{-- Summary --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="stat-card danger">
            <div class="stat-icon danger"><i class="fa fa-money-bill-wave"></i></div>
            <div class="stat-value"><span class="stat-currency">Br</span>{{ number_format($totalAmount,0) }}</div>
            <div class="stat-label">Total Expenses</div>
            <div class="stat-change neutral"><i class="fa fa-filter me-1"></i>Filtered period</div>
            <i class="fa fa-money-bill-wave watermark"></i>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-3">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end filter-form">
    <div class="col-12 col-md-3">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fa fa-search"></i></span>
            <input type="text" name="search" class="form-control live-search"
                   placeholder="Title, reference…" value="{{ request('search') }}">
        </div>
    </div>
    <div class="col-auto">
        <select name="category_id" class="form-select form-select-sm ts-select">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <input type="date" name="date_from" class="form-control form-control-sm"
               value="{{ request('date_from') }}">
    </div>
    <div class="col-auto">
        <input type="date" name="date_to" class="form-control form-control-sm"
               value="{{ request('date_to') }}">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary">
            <i class="fa fa-filter me-1"></i>Filter
        </button>
        @if(request()->hasAny(['search','category_id','date_from','date_to']))
            <a href="{{ route('expenses.index') }}" class="btn btn-sm btn-outline-secondary ms-1">
                <i class="fa fa-xmark"></i>
            </a>
        @endif
    </div>
</form>
</div>
</div>

{{-- Table --}}
<div class="card">
<div class="table-responsive">
<table class="table">
    <thead>
        <tr>
            <th>Ref #</th>
            <th>Title</th>
            <th>Category</th>
            <th>Date</th>
            <th>Warehouse</th>
            <th>Payment</th>
            <th>Amount</th>
            <th>By</th>
            <th class="text-end">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($expenses as $exp)
        <tr>
            <td>
                <a href="{{ route('expenses.show', $exp) }}"
                   class="fw-semibold text-primary">{{ $exp->expense_number }}</a>
            </td>
            <td>
                <div class="fw-medium small">{{ $exp->title }}</div>
                @if($exp->reference_number)
                    <div class="text-muted" style="font-size:.72rem">
                        <i class="fa fa-receipt me-1"></i>{{ $exp->reference_number }}
                    </div>
                @endif
            </td>
            <td>
                <span class="badge bg-secondary-subtle text-secondary">
                    {{ $exp->category?->name ?? '—' }}
                </span>
            </td>
            <td class="text-muted small">{{ $exp->expense_date->format('M d, Y') }}</td>
            <td class="text-muted small">{{ $exp->warehouse?->name ?? 'Company-wide' }}</td>
            <td class="small">{{ $exp->payment_method_label }}</td>
            <td class="fw-semibold text-danger">Br {{ number_format($exp->amount, 2) }}</td>
            <td class="text-muted small">{{ $exp->user?->name }}</td>
            <td class="text-end">
                <a href="{{ route('expenses.show', $exp) }}"
                   class="btn btn-action btn-outline-secondary me-1">
                    <i class="fa fa-eye"></i>
                </a>
                @if(auth()->user()->hasPermission('expenses.edit'))
                <a href="{{ route('expenses.edit', $exp) }}"
                   class="btn btn-action btn-outline-primary me-1">
                    <i class="fa fa-pen"></i>
                </a>
                @endif
                @if(auth()->user()->hasPermission('expenses.delete'))
                <button class="btn btn-action btn-outline-danger"
                        data-delete-url="{{ route('expenses.destroy', $exp) }}"
                        data-delete-message="Delete expense '{{ $exp->title }}'?">
                    <i class="fa fa-trash"></i>
                </button>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" class="text-center text-muted py-5">
                <i class="fa fa-receipt fs-2 d-block mb-2 opacity-25"></i>
                No expenses recorded yet.
                <a href="{{ route('expenses.create') }}">Add first expense.</a>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>
@if($expenses->hasPages())
    <div class="card-body border-top py-3">{{ $expenses->links() }}</div>
@endif
</div>

{{-- Delete confirm modal (reuses global modal) --}}
<form id="deleteForm" method="POST" style="display:none">
    @csrf @method('DELETE')
</form>
@endsection
