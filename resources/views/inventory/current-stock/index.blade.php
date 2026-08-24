@extends('layouts.app')
@section('title', 'Current Stock')
@section('breadcrumb')
    <li class="breadcrumb-item active">Inventory</li>
    <li class="breadcrumb-item active">Current Stock</li>
@endsection
@section('content')

@include('partials.page-header', [
    'title'    => 'Current Stock',
    'subtitle' => 'Live snapshot of all inventory levels',
])

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary-soft"><i class="fa fa-boxes-stacked"></i></div>
            <div class="stat-body">
                <div class="stat-value">Br {{ number_format($summary['total_parts_value'] + $summary['total_vehicles_value'], 0) }}</div>
                <div class="stat-label">Total Inventory Value</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success-soft"><i class="fa fa-gears"></i></div>
            <div class="stat-body">
                <div class="stat-value">Br {{ number_format($summary['total_parts_value'], 0) }}</div>
                <div class="stat-label">Parts Stock Value</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning-soft"><i class="fa fa-motorcycle"></i></div>
            <div class="stat-body">
                <div class="stat-value">Br {{ number_format($summary['total_vehicles_value'], 0) }}</div>
                <div class="stat-label">Vehicles Stock Value</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-danger-soft"><i class="fa fa-triangle-exclamation"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $summary['low_parts'] + $summary['low_vehicles'] }}</div>
                <div class="stat-label">Low / Out of Stock</div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="stockTabs">
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'parts' ? 'active' : '' }}" href="?tab=parts">
            <i class="fa fa-gears me-1"></i>Spare Parts
            @if($summary['low_parts'] > 0)
                <span class="badge bg-warning text-dark ms-1">{{ $summary['low_parts'] }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'vehicles' ? 'active' : '' }}" href="?tab=vehicles">
            <i class="fa fa-motorcycle me-1"></i>Vehicles
            @if($summary['low_vehicles'] > 0)
                <span class="badge bg-warning text-dark ms-1">{{ $summary['low_vehicles'] }}</span>
            @endif
        </a>
    </li>
</ul>

<!-- Filters -->
<div class="card mb-3">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="col-12 col-md-4">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fa fa-search"></i></span>
            <input type="text" name="search" class="form-control"
                   placeholder="{{ $tab === 'parts' ? 'Part name or number…' : 'Model name or code…' }}"
                   value="{{ request('search') }}">
        </div>
    </div>
    @if($tab === 'parts')
    <div class="col-auto">
        <select name="category" class="form-select form-select-sm">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
    @else
    <div class="col-auto">
        <select name="vehicle_type" class="form-select form-select-sm">
            <option value="">All Types</option>
            @foreach($vehicleTypes as $vt)
                <option value="{{ $vt->id }}" {{ request('vehicle_type') == $vt->id ? 'selected' : '' }}>{{ $vt->name }}</option>
            @endforeach
        </select>
    </div>
    @endif
    <div class="col-auto">
        <select name="stock_filter" class="form-select form-select-sm">
            <option value="">All Stock</option>
            <option value="ok"  {{ request('stock_filter') === 'ok'  ? 'selected' : '' }}>In Stock</option>
            <option value="low" {{ request('stock_filter') === 'low' ? 'selected' : '' }}>Low Stock</option>
            <option value="out" {{ request('stock_filter') === 'out' ? 'selected' : '' }}>Out of Stock</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-filter me-1"></i>Filter</button>
        @if(request()->hasAny(['search','category','vehicle_type','stock_filter']))
            <a href="?tab={{ $tab }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="fa fa-xmark"></i></a>
        @endif
    </div>
</form>
</div>
</div>

@if($tab === 'parts')
<!-- SPARE PARTS TABLE -->
<div class="card">
<div class="table-responsive">
<table class="table">
    <thead>
        <tr>
            <th>Part</th>
            <th>Category</th>
            <th>Unit</th>
            <th>Current Stock</th>
            <th>Reorder Level</th>
            <th>Status</th>
            <th>Stock Value</th>
            <th class="text-end">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($parts as $part)
        <tr>
            <td>
                <a href="{{ route('catalog.spare-parts.show', $part) }}" class="fw-semibold text-dark text-decoration-none">
                    {{ $part->name }}
                </a>
                <div class="text-muted" style="font-size:.75rem">{{ $part->part_number }}</div>
            </td>
            <td class="text-muted small">{{ $part->category->name }}</td>
            <td class="text-muted small">{{ $part->unit->abbreviation }}</td>
            <td>
                <span class="fw-bold fs-6 {{ $part->isOutOfStock() ? 'text-danger' : ($part->isLowStock() ? 'text-warning' : 'text-success') }}">
                    {{ $part->current_stock }}
                </span>
            </td>
            <td class="text-muted">{{ $part->reorder_level }}</td>
            <td>
                <span class="stock-pill {{ $part->stock_status === 'out_of_stock' ? 'out' : ($part->stock_status === 'low' ? 'low' : 'in-stock') }}">
                    {{ $part->stock_status === 'out_of_stock' ? 'Out of Stock' : ($part->stock_status === 'low' ? 'Low Stock' : 'In Stock') }}
                </span>
            </td>
            <td class="text-muted small">Br {{ number_format($part->current_stock * $part->buying_price, 2) }}</td>
            <td class="text-end">
                <a href="{{ route('inventory.stock-in.create') }}" class="btn btn-action btn-outline-success" title="Add Stock">
                    <i class="fa fa-plus"></i>
                </a>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center text-muted py-5">No parts found.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
@if($parts->hasPages())
<div class="card-body border-top py-3">{{ $parts->links() }}</div>
@endif
</div>

@else
<!-- VEHICLES TABLE -->
<div class="card">
<div class="table-responsive">
<table class="table">
    <thead>
        <tr>
            <th>Vehicle Model</th>
            <th>Type</th>
            <th>Current Stock</th>
            <th>Reorder Level</th>
            <th>Status</th>
            <th>Stock Value</th>
            <th class="text-end">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($vehicles as $vs)
        @php $vm = $vs->vehicleModel; @endphp
        <tr>
            <td>
                <a href="{{ route('catalog.vehicle-models.show', $vm) }}" class="fw-semibold text-dark text-decoration-none">
                    {{ $vm->brand }} {{ $vm->model_name }}
                </a>
                <div class="text-muted" style="font-size:.75rem">{{ $vm->model_code }} — {{ $vm->engine_cc }}</div>
            </td>
            <td>
                <span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:.72rem;padding:3px 8px;border-radius:5px">{{ $vm->vehicleType->name }}</span>
            </td>
            <td>
                <span class="fw-bold fs-6 {{ $vs->current_stock <= 0 ? 'text-danger' : ($vs->isLow() ? 'text-warning' : 'text-success') }}">
                    {{ $vs->current_stock }}
                </span>
            </td>
            <td class="text-muted">{{ $vs->reorder_level }}</td>
            <td>
                <span class="stock-pill {{ $vs->current_stock <= 0 ? 'out' : ($vs->isLow() ? 'low' : 'in-stock') }}">
                    {{ $vs->stock_status_label }}
                </span>
            </td>
            <td class="text-muted small">Br {{ number_format($vs->current_stock * $vm->buying_price, 2) }}</td>
            <td class="text-end">
                <a href="{{ route('inventory.stock-in.create') }}" class="btn btn-action btn-outline-success" title="Add Stock">
                    <i class="fa fa-plus"></i>
                </a>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted py-5">No vehicles found.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
@if($vehicles->hasPages())
<div class="card-body border-top py-3">{{ $vehicles->links() }}</div>
@endif
</div>
@endif

@endsection
