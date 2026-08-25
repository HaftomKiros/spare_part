@extends('layouts.app')
@section('title', 'Stock Movement History')
@section('breadcrumb')
    <li class="breadcrumb-item active">Inventory</li>
    <li class="breadcrumb-item active">History</li>
@endsection
@section('content')

@include('partials.page-header', [
    'title'    => 'Stock Movement History',
    'subtitle' => 'Complete audit trail of all stock changes',
])

<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success-soft"><i class="fa fa-arrow-down-to-bracket"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ number_format($totalIn) }}</div>
                <div class="stat-label">Total Stock-Entry Events</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-danger-soft"><i class="fa fa-arrow-up-from-bracket"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ number_format($totalOut) }}</div>
                <div class="stat-label">Total Stock-Out Events</div>
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
            <input type="text" name="search" class="form-control live-search" placeholder="Item name or part number…" value="{{ request('search') }}">
        </div>
    </div>
    <div class="col-auto">
        <select name="item_type" class="form-select form-select-sm ts-select">
            <option value="">All Items</option>
            <option value="vehicle"    {{ request('item_type') === 'vehicle'    ? 'selected' : '' }}>Vehicles</option>
            <option value="spare_part" {{ request('item_type') === 'spare_part' ? 'selected' : '' }}>Spare Parts</option>
        </select>
    </div>
    <div class="col-auto">
        <select name="movement_type" class="form-select form-select-sm ts-select">
            <option value="">All Movements</option>
            @foreach($movementTypes as $key => $label)
                <option value="{{ $key }}" {{ request('movement_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
    </div>
    <div class="col-auto">
        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
    </div>
    <div class="col-auto">
        <select name="warehouse_id" class="form-select form-select-sm ts-select" style="min-width:140px">
            <option value="">All Warehouses</option>
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-filter me-1"></i>Filter</button>
        @if(request()->hasAny(['search','item_type','movement_type','date_from','date_to','warehouse_id']))
            <a href="{{ route('inventory.history') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="fa fa-xmark"></i></a>
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
            <th>Date & Time</th>
            <th>Item</th>
            <th>Movement</th>
            <th>Direction</th>
            <th>Qty</th>
            <th>Before</th>
            <th>After</th>
            <th>Unit Cost</th>
            <th class="d-none d-lg-table-cell">Warehouse</th>
            <th>Reference</th>
            <th>By</th>
        </tr>
    </thead>
    <tbody>
        @forelse($movements as $mv)
        <tr>
            <td class="text-muted small">{{ $mv->created_at->format('M d, Y H:i') }}</td>
            <td>
                <div class="fw-semibold small">{{ $mv->item_name }}</div>
                <span class="badge bg-{{ $mv->item_type === 'vehicle' ? 'primary' : 'success' }} bg-opacity-10 text-{{ $mv->item_type === 'vehicle' ? 'primary' : 'success' }}" style="font-size:.68rem">
                    {{ $mv->item_type === 'vehicle' ? 'Vehicle' : 'Part' }}
                </span>
            </td>
            <td>
                <span class="badge bg-{{ $mv->movement_type_badge }}">{{ $mv->movement_type_label }}</span>
            </td>
            <td>
                <span class="{{ $mv->isInward() ? 'text-success' : 'text-danger' }}">
                    <i class="fa fa-{{ $mv->isInward() ? 'arrow-down' : 'arrow-up' }} me-1"></i>
                    {{ $mv->isInward() ? 'IN' : 'OUT' }}
                </span>
            </td>
            <td class="fw-bold {{ $mv->isInward() ? 'text-success' : 'text-danger' }}">
                {{ $mv->isInward() ? '+' : '-' }}{{ $mv->quantity }}
            </td>
            <td class="text-muted">{{ $mv->quantity_before }}</td>
            <td class="fw-semibold">{{ $mv->quantity_after }}</td>
            <td class="text-muted small">{{ $mv->unit_cost > 0 ? 'Br '.number_format($mv->unit_cost,2) : '—' }}</td>
            <td class="text-muted small d-none d-lg-table-cell">{{ $mv->warehouse?->name ?? '—' }}</td>
            <td class="text-muted small">
                {{ $mv->reference_type ? class_basename($mv->reference_type).' #'.$mv->reference_id : '—' }}
            </td>
            <td class="small">{{ $mv->user->name }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="10" class="text-center text-muted py-5">
                <i class="fa fa-clock-rotate-left fs-2 d-block mb-2 opacity-25"></i>
                No stock movements found.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>
@if($movements->hasPages())
<div class="card-body border-top py-3">{{ $movements->links() }}</div>
@endif
</div>
@endsection
