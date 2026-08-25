@extends('layouts.app')
@section('title', 'Stock Entry')
@section('breadcrumb')
    <li class="breadcrumb-item active">Inventory</li>
    <li class="breadcrumb-item active">Stock Entry</li>
@endsection
@section('content')
@include('partials.page-header', [
    'title'    => 'Stock Entry',
    'subtitle' => 'Record stock additions to inventory',
    'actions'  => [['label' => 'New Entry', 'route' => 'inventory.stock-in.create', 'icon' => 'fa-plus', 'class' => 'btn-success']],
])

<!-- Filters -->
<div class="card mb-3">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end filter-form">
    <div class="col-12 col-md-4">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fa fa-search"></i></span>
            <input type="text" name="search" class="form-control live-search" placeholder="Item name or part number…" value="{{ request('search') }}">
        </div>
    </div>
    <div class="col-auto">
        <select name="type" class="form-select form-select-sm ts-select">
            <option value="">All Types</option>
            <option value="opening"       {{ request('type') === 'opening'       ? 'selected' : '' }}>Opening Stock</option>
            <option value="purchase"      {{ request('type') === 'purchase'      ? 'selected' : '' }}>Purchase</option>
            <option value="adjustment_in" {{ request('type') === 'adjustment_in' ? 'selected' : '' }}>Manual Adjustment</option>
            <option value="return_in"     {{ request('type') === 'return_in'     ? 'selected' : '' }}>Return In</option>
        </select>
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
        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="From">
    </div>
    <div class="col-auto">
        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="To">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-filter me-1"></i>Filter</button>
        @if(request()->hasAny(['search','type','date_from','date_to','warehouse_id']))
            <a href="{{ route('inventory.stock-in.index') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="fa fa-xmark"></i></a>
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
            <th>Type</th>
            <th>Category</th>
            <th>Qty Added</th>
            <th>Before</th>
            <th>After</th>
            <th>Unit Cost</th>
            <th class="d-none d-lg-table-cell">Warehouse</th>
            <th>By</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
        @forelse($movements as $mv)
        <tr>
            <td class="text-muted small">{{ $mv->created_at->format('M d, Y H:i') }}</td>
            <td>
                <div class="fw-semibold small">{{ $mv->item_name }}</div>
                <div class="text-muted" style="font-size:.72rem">
                    {{ $mv->item_type === 'vehicle' ? $mv->vehicleModel?->vehicleType?->name : $mv->sparePart?->part_number }}
                </div>
            </td>
            <td><span class="badge bg-success">{{ $mv->movement_type_label }}</span></td>
            <td class="text-muted small">
                {{ $mv->item_type === 'vehicle' ? ($mv->vehicleModel?->vehicleType?->name ?? '—') : ($mv->sparePart?->category?->name ?? '—') }}
            </td>
            <td class="fw-bold text-success">+{{ $mv->quantity }}</td>
            <td class="text-muted">{{ $mv->quantity_before }}</td>
            <td class="fw-semibold">{{ $mv->quantity_after }}</td>
            <td class="text-muted">{{ $mv->unit_cost > 0 ? 'Br '.number_format($mv->unit_cost,2) : '—' }}</td>
            <td class="text-muted small d-none d-lg-table-cell">{{ $mv->warehouse?->name ?? '—' }}</td>
            <td class="small">{{ $mv->user->name }}</td>
            <td class="text-muted small">{{ $mv->notes ? Str::limit($mv->notes, 40) : '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="11" class="text-center text-muted py-5">
                <i class="fa fa-arrow-down-to-bracket fs-2 d-block mb-2 opacity-25"></i>
                No stock entry records found.
                <a href="{{ route('inventory.stock-in.create') }}">Add stock now.</a>
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
