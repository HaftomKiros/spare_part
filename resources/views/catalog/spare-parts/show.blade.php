@extends('layouts.app')
@section('title', $sparePart->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.spare-parts.index') }}">Spare Parts</a></li>
    <li class="breadcrumb-item active">{{ $sparePart->part_number }}</li>
@endsection
@section('content')
@include('partials.page-header', [
    'title'    => $sparePart->name,
    'subtitle' => $sparePart->part_number . ($sparePart->oem_number ? ' · OEM: ' . $sparePart->oem_number : ''),
    'actions'  => [['label' => 'Edit Part', 'route' => 'catalog.spare-parts.edit', 'route_params' => $sparePart, 'icon' => 'fa-pen', 'class' => 'btn-outline-primary']],
])

<div class="row g-3">
<div class="col-12 col-md-6 col-xl-3">
<div class="card">
<div class="card-header"><i class="fa fa-info-circle me-2 text-primary"></i>Details</div>
<div class="card-body">
    <table class="table table-sm table-borderless mb-0 small">
        <tr><th class="text-muted fw-normal" style="width:45%">Part #</th><td class="fw-semibold">{{ $sparePart->part_number }}</td></tr>
        <tr><th class="text-muted fw-normal">OEM #</th><td>{{ $sparePart->oem_number ?? '—' }}</td></tr>
        <tr><th class="text-muted fw-normal">Category</th><td>{{ $sparePart->category->name }}</td></tr>
        <tr><th class="text-muted fw-normal">Unit</th><td>{{ $sparePart->unit->name }}</td></tr>
        <tr><th class="text-muted fw-normal">Location</th><td>{{ $sparePart->location ?? '—' }}</td></tr>
        <tr><th class="text-muted fw-normal">Status</th>
            <td><span class="badge badge-status-{{ $sparePart->status }}">{{ ucfirst($sparePart->status) }}</span></td>
        </tr>
    </table>
</div>
</div>
</div>

<div class="col-12 col-md-6 col-xl-3">
<div class="card">
<div class="card-header"><i class="fa fa-warehouse me-2 text-primary"></i>Stock Status</div>
<div class="card-body text-center">
    <div class="display-5 fw-bold {{ $unsoldStock <= 0 ? 'text-danger' : ($unsoldStock <= $sparePart->reorder_level ? 'text-warning' : 'text-success') }}">
        {{ $unsoldStock }}
    </div>
    <div class="text-muted mb-2">{{ $sparePart->unit->name }}(s) unsold</div>
    <span class="stock-pill {{ $unsoldStock <= 0 ? 'out' : ($unsoldStock <= $sparePart->reorder_level ? 'low' : 'in-stock') }}">
        {{ $unsoldStock <= 0 ? 'Out of Stock' : ($unsoldStock <= $sparePart->reorder_level ? 'Low Stock' : 'In Stock') }}
    </span>
    <div class="text-muted small mt-3">Reorder level: {{ $sparePart->reorder_level }}</div>
    <a href="{{ route('inventory.stock-in.index') }}" class="btn btn-sm btn-success mt-2 d-block">
        <i class="fa fa-plus me-1"></i>Add Stock
    </a>
</div>
</div>
</div>

<div class="col-12 col-md-6 col-xl-3">
<div class="card">
<div class="card-header"><i class="fa fa-motorcycle me-2 text-primary"></i>Compatible Vehicles</div>
<div class="card-body" style="max-height:200px;overflow-y:auto">
    @forelse($sparePart->compatibleVehicles as $v)
        <div class="d-flex align-items-center gap-2 py-1 border-bottom">
            <span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:.72rem;padding:3px 8px;border-radius:5px">{{ $v->vehicleType->name }}</span>
            <span class="small">{{ $v->brand }} {{ $v->model_name }}</span>
        </div>
    @empty
        <p class="text-muted text-center small mt-2">No vehicles linked.</p>
    @endforelse
</div>
</div>
</div>

<!-- Movement History -->
<div class="col-12">
<div class="card">
<div class="card-header"><i class="fa fa-clock-rotate-left me-2 text-primary"></i>Recent Stock Movements</div>
<div class="table-responsive">
<table class="table">
    <thead>
        <tr><th>Date</th><th>Type</th><th>Qty</th><th>Before</th><th>After</th><th>Cost</th><th>Reference</th><th>By</th></tr>
    </thead>
    <tbody>
        @forelse($recentMovements as $mv)
        <tr>
            <td class="text-muted small">{{ $mv->created_at->format('M d, Y H:i') }}</td>
            <td><span class="badge bg-{{ $mv->movement_type_badge }}">{{ $mv->movement_type_label }}</span></td>
            <td class="fw-semibold {{ $mv->isInward() ? 'text-success' : 'text-danger' }}">
                {{ $mv->isInward() ? '+' : '-' }}{{ $mv->quantity }}
            </td>
            <td>{{ $mv->quantity_before }}</td>
            <td>{{ $mv->quantity_after }}</td>
            <td class="text-muted small">Br {{ number_format($mv->unit_cost, 2) }}</td>
            <td class="text-muted small">{{ $mv->reference_type ? class_basename($mv->reference_type) . ' #' . $mv->reference_id : '—' }}</td>
            <td>{{ $mv->user->name }}</td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center text-muted py-3">No movements recorded.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</div>
</div>
</div>
@endsection
