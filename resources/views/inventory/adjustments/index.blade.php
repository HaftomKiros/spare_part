@extends('layouts.app')
@section('title', 'Stock Adjustments')
@section('breadcrumb')
    <li class="breadcrumb-item active">Inventory</li>
    <li class="breadcrumb-item active">Adjustments</li>
@endsection
@section('content')
@include('partials.page-header', [
    'title'    => 'Stock Adjustments',
    'subtitle' => 'Correct stock counts due to damage, loss, miscounts or write-offs',
    'actions'  => [['label' => 'New Adjustment', 'route' => 'inventory.adjustments.create', 'icon' => 'fa-plus']],
])

<div class="card mb-3">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end filter-form">
    <div class="col-12 col-md-4">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fa fa-search"></i></span>
            <input type="text" name="search" class="form-control live-search" placeholder="Adjustment # or reason…" value="{{ request('search') }}">
        </div>
    </div>
    <div class="col-auto">
        <select name="type" class="form-select form-select-sm ts-select">
            <option value="">All Types</option>
            <option value="increase" {{ request('type') === 'increase' ? 'selected' : '' }}>Increase</option>
            <option value="decrease" {{ request('type') === 'decrease' ? 'selected' : '' }}>Decrease</option>
            <option value="recount"  {{ request('type') === 'recount'  ? 'selected' : '' }}>Recount</option>
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
        @if(request()->hasAny(['search','type','date_from','date_to']))
            <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="fa fa-xmark"></i></a>
        @endif
    </div>
</form>
</div>
</div>

<div class="card">
<div class="table-responsive">
<table class="table">
    <thead>
        <tr>
            <th>Adj. Number</th>
            <th>Date</th>
            <th>Type</th>
            <th>Items</th>
            <th>Reason</th>
            <th>Status</th>
            <th>By</th>
            <th class="text-end">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($adjustments as $adj)
        <tr>
            <td class="fw-semibold text-primary">{{ $adj->adjustment_number }}</td>
            <td class="text-muted">{{ $adj->adjustment_date->format('M d, Y') }}</td>
            <td>
                <span class="badge bg-{{ $adj->adjustment_type === 'increase' ? 'success' : ($adj->adjustment_type === 'decrease' ? 'danger' : 'warning') }}">
                    {{ ucfirst($adj->adjustment_type) }}
                </span>
            </td>
            <td><span class="badge bg-secondary">{{ $adj->items_count }}</span></td>
            <td class="text-muted small">{{ Str::limit($adj->reason, 60) }}</td>
            <td><span class="badge bg-{{ $adj->status_badge }}">{{ ucfirst($adj->status) }}</span></td>
            <td class="small">{{ $adj->user->name }}</td>
            <td class="text-end">
                <a href="{{ route('inventory.adjustments.show', $adj) }}" class="btn btn-action btn-outline-primary">
                    <i class="fa fa-eye"></i>
                </a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" class="text-center text-muted py-5">
                <i class="fa fa-sliders fs-2 d-block mb-2 opacity-25"></i>
                No adjustments recorded yet.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>
@if($adjustments->hasPages())
<div class="card-body border-top py-3">{{ $adjustments->links() }}</div>
@endif
</div>
@endsection
