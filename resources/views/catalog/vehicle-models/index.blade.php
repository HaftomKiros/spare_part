@extends('layouts.app')
@section('title', 'Vehicle Models')

@section('breadcrumb')
    <li class="breadcrumb-item active">Catalog</li>
    <li class="breadcrumb-item active">Vehicle Models</li>
@endsection

@section('content')
@include('partials.page-header', [
    'title'    => 'Vehicle Models',
    'subtitle' => 'All Bajaj two-wheeler and three-wheeler models',
    'actions'  => [['label' => 'Add Model', 'route' => 'catalog.vehicle-models.create', 'icon' => 'fa-plus']],
])

<div class="card mb-3">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end filter-form">
    <div class="col-12 col-md-4">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fa fa-search"></i></span>
            <input type="text" name="search" class="form-control live-search" placeholder="Search model name, code…" value="{{ request('search') }}">
        </div>
    </div>
    <div class="col-auto">
        <select name="type" class="form-select form-select-sm ts-select">
            <option value="">All Types</option>
            @foreach($types as $t)
                <option value="{{ $t->id }}" {{ request('type') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm ts-select">
            <option value="">All Status</option>
            <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-filter me-1"></i>Filter</button>
        @if(request()->hasAny(['search','type','status']))
            <a href="{{ route('catalog.vehicle-models.index') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="fa fa-xmark"></i></a>
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
            <th>#</th>
            <th>Model</th>
            <th>Type</th>
            <th>Engine</th>
            <th>Buy Price</th>
            <th>Sell Price</th>
            <th>Stock</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($models as $model)
        <tr>
            <td class="text-muted">{{ $models->firstItem() + $loop->index }}</td>
            <td>
                <a href="{{ route('catalog.vehicle-models.show', $model) }}" class="fw-semibold text-dark text-decoration-none">
                    {{ $model->brand }} {{ $model->model_name }}
                </a>
                @if($model->model_code)
                    <div class="text-muted small">{{ $model->model_code }}</div>
                @endif
            </td>
            <td>
                <span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:.72rem;padding:3px 8px;border-radius:5px">{{ $model->vehicleType->name }}</span>
            </td>
            <td class="text-muted">{{ $model->engine_cc ?? '—' }}</td>
            <td>Br {{ number_format($model->buying_price, 2) }}</td>
            <td>
                @if($model->selling_price_min > 0 || $model->selling_price_max > 0)
                    <span class="small">
                        <span class="text-muted">Br {{ number_format($model->selling_price_min, 2) }}</span>
                        <span class="text-muted mx-1">—</span>
                        <span class="fw-semibold text-success">Br {{ number_format($model->selling_price_max, 2) }}</span>
                    </span>
                @else
                    <span class="text-muted">—</span>
                @endif
            </td>
            <td>
                @php $stock = $model->stock; $qty = $stock?->current_stock ?? 0; @endphp
                <span class="stock-pill {{ $qty <= 0 ? 'out' : ($stock?->isLow() ? 'low' : 'in-stock') }}">
                    {{ $qty }}
                </span>
            </td>
            <td><span class="badge badge-status-{{ $model->status }}">{{ ucfirst($model->status) }}</span></td>
            <td class="text-end">
                <a href="{{ route('catalog.vehicle-models.show', $model) }}" class="btn btn-action btn-outline-secondary me-1" title="View"><i class="fa fa-eye"></i></a>
                <a href="{{ route('catalog.vehicle-models.edit', $model) }}" class="btn btn-action btn-outline-primary me-1" title="Edit"><i class="fa fa-pen"></i></a>
                <button class="btn btn-action btn-outline-danger" title="Delete"
                        data-delete-url="{{ route('catalog.vehicle-models.destroy', $model) }}"
                        data-delete-message="Delete model '{{ $model->full_name }}'? This action cannot be undone.">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" class="text-center text-muted py-5">
                <i class="fa fa-car fs-2 d-block mb-2 opacity-25"></i>
                No vehicle models found.
                <a href="{{ route('catalog.vehicle-models.create') }}">Add one now.</a>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>
@if($models->hasPages())
<div class="card-body border-top py-3">{{ $models->links() }}</div>
@endif
</div>

@include('partials.delete-modal')
@endsection
