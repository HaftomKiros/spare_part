@extends('layouts.app')
@section('title','Low Stock Report')
@section('breadcrumb')
    <li class="breadcrumb-item active">Reports</li>
    <li class="breadcrumb-item active">Low Stock</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'Low Stock Alerts','subtitle'=>'Items that need immediate restocking'])

<!-- Warehouse Filter -->
<div class="card mb-4">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
    @include('partials.warehouse-filter')
    <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary mt-3"><i class="fa fa-filter me-1"></i>Apply</button></div>
    @if($warehouseId)
    <div class="col-auto ms-auto mt-3">
        <span class="badge bg-primary-subtle text-primary-emphasis"><i class="fa fa-warehouse me-1"></i>{{ $warehouses->find($warehouseId)?->name }}</span>
    </div>
    @endif
</form>
</div>
</div>

<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon bg-danger-soft"><i class="fa fa-circle-xmark"></i></div>
        <div class="stat-body"><div class="stat-value">{{ count($outParts) }}</div><div class="stat-label">Out of Stock Parts</div></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon bg-warning-soft"><i class="fa fa-triangle-exclamation"></i></div>
        <div class="stat-body"><div class="stat-value">{{ count($lowParts) }}</div><div class="stat-label">Low Stock Parts</div></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon bg-danger-soft"><i class="fa fa-motorcycle"></i></div>
        <div class="stat-body"><div class="stat-value">{{ count($outVehicles) }}</div><div class="stat-label">Out of Stock Vehicles</div></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon bg-warning-soft"><i class="fa fa-motorcycle"></i></div>
        <div class="stat-body"><div class="stat-value">{{ count($lowVehicles) }}</div><div class="stat-label">Low Stock Vehicles</div></div></div>
    </div>
</div>

{{-- Out of Stock Parts --}}
@if(count($outParts))
<div class="card mb-4">
<div class="card-header text-danger"><i class="fa fa-circle-xmark me-2"></i>Out of Stock — Spare Parts ({{ count($outParts) }})</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Part</th><th>Category</th><th>Unit</th><th>Stock</th><th>Reorder</th><th class="text-end">Action</th></tr></thead>
    <tbody>
        @foreach($outParts as $p)
        <tr>
            <td>
                <div class="fw-semibold small">{{ $p->name }}</div>
                <div class="text-muted" style="font-size:.72rem">{{ $p->part_number }}</div>
            </td>
            <td class="text-muted small">{{ $p->category->name ?? $p->category ?? '—' }}</td>
            <td class="text-muted small">{{ $p->unit->abbreviation ?? $p->unit_abbr ?? '—' }}</td>
            <td><span class="stock-pill out">0</span></td>
            <td class="text-muted">{{ $p->reorder_level }}</td>
            <td class="text-end">
                <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-outline-primary">Order</a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>
</div>
@endif

{{-- Low Stock Parts --}}
@if(count($lowParts))
<div class="card mb-4">
<div class="card-header text-warning"><i class="fa fa-triangle-exclamation me-2"></i>Low Stock — Spare Parts ({{ count($lowParts) }})</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Part</th><th>Category</th><th>Unit</th><th>Stock</th><th>Reorder</th><th>Deficit</th><th class="text-end">Action</th></tr></thead>
    <tbody>
        @foreach($lowParts as $p)
        <tr>
            <td>
                <div class="fw-semibold small">{{ $p->name }}</div>
                <div class="text-muted" style="font-size:.72rem">{{ $p->part_number }}</div>
            </td>
            <td class="text-muted small">{{ $p->category->name ?? $p->category ?? '—' }}</td>
            <td class="text-muted small">{{ $p->unit->abbreviation ?? $p->unit_abbr ?? '—' }}</td>
            <td><span class="stock-pill low">{{ $p->current_stock }}</span></td>
            <td class="text-muted">{{ $p->reorder_level }}</td>
            <td class="text-danger fw-semibold">{{ max(0, $p->reorder_level - $p->current_stock) }} needed</td>
            <td class="text-end">
                <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-outline-warning">Reorder</a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>
</div>
@endif

{{-- Vehicle Alerts --}}
@if(count($lowVehicles) || count($outVehicles))
<div class="card">
<div class="card-header"><i class="fa fa-motorcycle me-2 text-primary"></i>Vehicle Stock Alerts</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Model</th><th>Type</th><th>Stock</th><th>Reorder</th><th>Status</th><th class="text-end">Action</th></tr></thead>
    <tbody>
        @foreach(array_merge(iterator_to_array($outVehicles instanceof \Illuminate\Support\Collection ? $outVehicles->getIterator() : new \ArrayIterator(is_array($outVehicles) ? $outVehicles : iterator_to_array($outVehicles))), iterator_to_array($lowVehicles instanceof \Illuminate\Support\Collection ? $lowVehicles->getIterator() : new \ArrayIterator(is_array($lowVehicles) ? $lowVehicles : iterator_to_array($lowVehicles)))) as $vs)
        @php
            $isEloquent = isset($vs->vehicleModel);
            $modelName  = $isEloquent ? $vs->vehicleModel->full_name : ($vs->brand . ' ' . $vs->model_name . ($vs->model_code ? ' ('.$vs->model_code.')' : ''));
            $typeName   = $isEloquent ? $vs->vehicleModel->vehicleType->name : $vs->type_name;
            $status     = $vs->current_stock <= 0 ? 'out' : 'low';
        @endphp
        <tr>
            <td><div class="fw-semibold small">{{ $modelName }}</div></td>
            <td><span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:.72rem;padding:3px 8px;border-radius:5px">{{ $typeName }}</span></td>
            <td><span class="stock-pill {{ $status }}">{{ $vs->current_stock }}</span></td>
            <td class="text-muted">{{ $vs->reorder_level }}</td>
            <td><span class="badge bg-{{ $status === 'out' ? 'danger' : 'warning' }}">{{ $status === 'out' ? 'Out of Stock' : 'Low Stock' }}</span></td>
            <td class="text-end">
                <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-outline-primary">Order</a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>
</div>
@endif

@if(!count($outParts) && !count($lowParts) && !count($lowVehicles) && !count($outVehicles))
<div class="card">
<div class="card-body text-center py-5">
    <i class="fa fa-check-circle fs-1 text-success mb-3 d-block"></i>
    <h5 class="fw-bold">All Stock Levels Healthy</h5>
    <p class="text-muted">No items are currently low or out of stock{{ $warehouseId ? ' in this warehouse' : '' }}.</p>
</div>
</div>
@endif
@endsection
