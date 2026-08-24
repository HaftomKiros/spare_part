@extends('layouts.app')
@section('title','Low Stock Report')
@section('breadcrumb')
    <li class="breadcrumb-item active">Reports</li>
    <li class="breadcrumb-item active">Low Stock</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'Low Stock Alerts','subtitle'=>'Items that need immediate restocking'])

<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon bg-danger-soft"><i class="fa fa-circle-xmark"></i></div>
        <div class="stat-body"><div class="stat-value">{{ $outParts->count() }}</div><div class="stat-label">Out of Stock Parts</div></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon bg-warning-soft"><i class="fa fa-triangle-exclamation"></i></div>
        <div class="stat-body"><div class="stat-value">{{ $lowParts->count() }}</div><div class="stat-label">Low Stock Parts</div></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon bg-danger-soft"><i class="fa fa-motorcycle"></i></div>
        <div class="stat-body"><div class="stat-value">{{ $outVehicles->count() }}</div><div class="stat-label">Out of Stock Vehicles</div></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon bg-warning-soft"><i class="fa fa-motorcycle"></i></div>
        <div class="stat-body"><div class="stat-value">{{ $lowVehicles->count() }}</div><div class="stat-label">Low Stock Vehicles</div></div></div>
    </div>
</div>

<!-- Out of Stock Parts -->
@if($outParts->count())
<div class="card mb-4">
<div class="card-header text-danger"><i class="fa fa-circle-xmark me-2"></i>Out of Stock — Spare Parts ({{ $outParts->count() }})</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Part</th><th>Category</th><th>Unit</th><th>Stock</th><th>Reorder</th><th>Buy Price</th><th class="text-end">Action</th></tr></thead>
    <tbody>
        @foreach($outParts as $p)
        <tr>
            <td><div class="fw-semibold small">{{ $p->name }}</div><div class="text-muted" style="font-size:.72rem">{{ $p->part_number }}</div></td>
            <td class="text-muted small">{{ $p->category->name }}</td>
            <td class="text-muted small">{{ $p->unit->abbreviation }}</td>
            <td><span class="stock-pill out">0</span></td>
            <td class="text-muted">{{ $p->reorder_level }}</td>
            <td class="text-muted small">Br {{ number_format($p->buying_price,2) }}</td>
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

<!-- Low Stock Parts -->
@if($lowParts->count())
<div class="card mb-4">
<div class="card-header text-warning"><i class="fa fa-triangle-exclamation me-2"></i>Low Stock — Spare Parts ({{ $lowParts->count() }})</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Part</th><th>Category</th><th>Unit</th><th>Stock</th><th>Reorder</th><th>Deficit</th><th class="text-end">Action</th></tr></thead>
    <tbody>
        @foreach($lowParts as $p)
        <tr>
            <td><div class="fw-semibold small">{{ $p->name }}</div><div class="text-muted" style="font-size:.72rem">{{ $p->part_number }}</div></td>
            <td class="text-muted small">{{ $p->category->name }}</td>
            <td class="text-muted small">{{ $p->unit->abbreviation }}</td>
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

<!-- Vehicles -->
@if($lowVehicles->count() || $outVehicles->count())
<div class="card">
<div class="card-header"><i class="fa fa-motorcycle me-2 text-primary"></i>Vehicle Stock Alerts</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Model</th><th>Type</th><th>Stock</th><th>Reorder</th><th>Status</th><th class="text-end">Action</th></tr></thead>
    <tbody>
        @foreach($lowVehicles as $vs)
        <tr>
            <td><div class="fw-semibold small">{{ $vs->vehicleModel->brand }} {{ $vs->vehicleModel->model_name }}</div></td>
            <td><span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:.72rem;padding:3px 8px;border-radius:5px">{{ $vs->vehicleModel->vehicleType->name }}</span></td>
            <td><span class="stock-pill {{ $vs->current_stock <= 0 ? 'out' : 'low' }}">{{ $vs->current_stock }}</span></td>
            <td class="text-muted">{{ $vs->reorder_level }}</td>
            <td><span class="stock-pill {{ $vs->current_stock <= 0 ? 'out' : 'low' }}">{{ $vs->stock_status_label }}</span></td>
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

@if(!$outParts->count() && !$lowParts->count() && !$lowVehicles->count() && !$outVehicles->count())
<div class="card">
<div class="card-body text-center py-5">
    <i class="fa fa-check-circle fs-1 text-success mb-3 d-block"></i>
    <h5 class="fw-bold">All Stock Levels Healthy</h5>
    <p class="text-muted">No items are currently low or out of stock.</p>
</div>
</div>
@endif
@endsection
