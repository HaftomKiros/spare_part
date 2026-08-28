@extends('layouts.app')
@section('title', $vehicleModel->full_name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.vehicle-models.index') }}">Vehicle Models</a></li>
    <li class="breadcrumb-item active">{{ $vehicleModel->model_name }}</li>
@endsection

@section('content')
@include('partials.page-header', [
    'title'    => $vehicleModel->full_name,
    'subtitle' => $vehicleModel->vehicleType->name,
    'actions'  => [
        ['label' => 'Edit Model', 'route' => 'catalog.vehicle-models.edit', 'route_params' => $vehicleModel, 'icon' => 'fa-pen', 'class' => 'btn-outline-primary'],
    ]
])

<div class="row g-3">
<!-- Info Card -->
<div class="col-12 col-md-6 col-xl-4">
<div class="card">
<div class="card-header"><i class="fa fa-info-circle me-2 text-primary"></i>Model Details</div>
<div class="card-body">
    <table class="table table-sm table-borderless mb-0">
        <tr><th class="text-muted fw-normal" style="width:45%">Brand</th><td class="fw-semibold">{{ $vehicleModel->brand }}</td></tr>
        <tr><th class="text-muted fw-normal">Model</th><td class="fw-semibold">{{ $vehicleModel->model_name }}</td></tr>
        <tr><th class="text-muted fw-normal">Code</th><td>{{ $vehicleModel->model_code ?? '—' }}</td></tr>
        <tr><th class="text-muted fw-normal">Type</th><td>{{ $vehicleModel->vehicleType->name }}</td></tr>
        <tr><th class="text-muted fw-normal">Engine</th><td>{{ $vehicleModel->engine_cc ?? '—' }}</td></tr>
        <tr><th class="text-muted fw-normal">Year</th><td>{{ $vehicleModel->year ?? '—' }}</td></tr>
        <tr><th class="text-muted fw-normal">Status</th>
            <td><span class="badge badge-status-{{ $vehicleModel->status }}">{{ ucfirst($vehicleModel->status) }}</span></td>
        </tr>
    </table>
</div>
</div>
</div>

<!-- Pricing & Stock -->
<div class="col-12 col-md-6 col-xl-4">
<div class="card">
<div class="card-header"><i class="fa fa-sack-dollar me-2 text-primary"></i>Pricing & Stock</div>
<div class="card-body">
    <div class="row g-3 text-center">
        <div class="col-6">
            <div class="p-3 rounded-3 bg-primary bg-opacity-10">
                <div class="fw-bold fs-5 text-primary">Br {{ number_format($vehicleModel->buying_price, 2) }}</div>
                <div class="small text-muted">Buying Price</div>
            </div>
        </div>
        <div class="col-6">
            <div class="p-3 rounded-3 bg-success bg-opacity-10">
                <div class="fw-bold fs-5 text-success">Br {{ number_format($vehicleModel->selling_price, 2) }}</div>
                <div class="small text-muted">Selling Price</div>
            </div>
        </div>
        <div class="col-6">
            <div class="p-3 rounded-3 bg-warning bg-opacity-10">
                <div class="fw-bold fs-5 text-warning">{{ $vehicleModel->profit_margin }}%</div>
                <div class="small text-muted">Profit Margin</div>
            </div>
        </div>
        <div class="col-6">
            @php $stock = $vehicleModel->stock; $qty = $stock?->current_stock ?? 0; @endphp
            <div class="p-3 rounded-3 {{ $qty <= 0 ? 'bg-danger' : ($vehicleModel->isLowStock() ? 'bg-warning' : 'bg-success') }} bg-opacity-10">
                <div class="fw-bold fs-5 {{ $qty <= 0 ? 'text-danger' : ($vehicleModel->isLowStock() ? 'text-warning' : 'text-success') }}">{{ $qty }}</div>
                <div class="small text-muted">In Stock</div>
            </div>
        </div>
    </div>
    @if($stock)
    <div class="mt-3 text-muted small text-center">
        Reorder level: <strong>{{ $stock->reorder_level }}</strong>
    </div>
    @endif
</div>
</div>
</div>

<!-- Compatible Parts Summary -->
<div class="col-12 col-xl-4">
<div class="card">
<div class="card-header"><i class="fa fa-gears me-2 text-primary"></i>Compatible Parts ({{ $vehicleModel->spareParts->count() }})</div>
<div class="card-body" style="max-height:220px;overflow-y:auto">
    @forelse($vehicleModel->spareParts as $part)
        <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
            <div>
                <a href="{{ route('catalog.spare-parts.show', $part) }}" class="text-primary" style="font-size:.85rem">{{ $part->name }}</a>
                <div class="text-muted" style="font-size:.75rem">{{ $part->part_number }}</div>
            </div>
            <span class="stock-pill {{ $part->stock_status === 'out_of_stock' ? 'out' : ($part->stock_status === 'low' ? 'low' : 'in-stock') }}">
                {{ $part->current_stock }}
            </span>
        </div>
    @empty
        <p class="text-muted text-center small mt-3">No compatible parts linked yet.</p>
    @endforelse
</div>
</div>
</div>
</div>

<!-- Stock History -->
<div class="col-12">
<div class="card">
<div class="card-header"><i class="fa fa-clock-rotate-left me-2 text-primary"></i>Recent Stock Movements</div>
<div class="table-responsive">
<table class="table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Qty</th>
            <th>Before</th>
            <th>After</th>
            <th>Reference</th>
            <th>By</th>
            <th>Notes</th>
        </tr>
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
            <td class="text-muted small">{{ $mv->reference_type ? class_basename($mv->reference_type) . ' #' . $mv->reference_id : '—' }}</td>
            <td>{{ $mv->user->name }}</td>
            <td class="text-muted small">{{ $mv->notes ?? '—' }}</td>
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
