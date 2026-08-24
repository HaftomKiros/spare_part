@extends('layouts.app')
@section('title', 'Vehicle Types')

@section('breadcrumb')
    <li class="breadcrumb-item active">Catalog</li>
    <li class="breadcrumb-item active">Vehicle Types</li>
@endsection

@section('content')
@include('partials.page-header', [
    'title'    => 'Vehicle Types',
    'subtitle' => 'Manage two-wheeler and three-wheeler categories',
    'actions'  => [['label' => 'Add Type', 'route' => 'catalog.vehicle-types.create', 'icon' => 'fa-plus']],
])

<div class="card mb-3">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-12 col-md-5">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fa fa-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Search vehicle types…" value="{{ request('search') }}">
        </div>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm">
            <option value="">All Status</option>
            <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-filter me-1"></i>Filter</button>
        @if(request()->hasAny(['search','status']))
            <a href="{{ route('catalog.vehicle-types.index') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="fa fa-xmark"></i></a>
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
                    <th>Name</th>
                    <th>Wheels</th>
                    <th>Models</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($types as $type)
                <tr>
                    <td class="text-muted">{{ $types->firstItem() + $loop->index }}</td>
                    <td>
                        <div class="fw-semibold">{{ $type->name }}</div>
                        @if($type->description)
                            <div class="text-muted small">{{ Str::limit($type->description, 60) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($type->wheel_count === 2)
                            <span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:.72rem;padding:3px 8px;border-radius:5px">
                                <i class="fa fa-motorcycle me-1"></i>2-Wheeler
                            </span>
                        @else
                            <span class="badge" style="background:#dcfce7;color:#166534;font-size:.72rem;padding:3px 8px;border-radius:5px">
                                <i class="fa fa-truck me-1"></i>3-Wheeler
                            </span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('catalog.vehicle-models.index', ['type' => $type->id]) }}" class="text-primary fw-medium">
                            {{ $type->vehicle_models_count }}
                        </a>
                    </td>
                    <td>
                        <span class="badge badge-status-{{ $type->status }}">{{ ucfirst($type->status) }}</span>
                    </td>
                    <td class="text-muted small">{{ $type->created_at->format('M d, Y') }}</td>
                    <td class="text-end">
                        <a href="{{ route('catalog.vehicle-types.edit', $type) }}" class="btn btn-action btn-outline-primary me-1">
                            <i class="fa fa-pen"></i>
                        </a>
                        <button class="btn btn-action btn-outline-danger"
                                data-delete-url="{{ route('catalog.vehicle-types.destroy', $type) }}"
                                data-delete-message="Delete vehicle type '{{ $type->name }}'? This cannot be undone.">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="fa fa-motorcycle fs-2 d-block mb-2 opacity-25"></i>
                        No vehicle types found.
                        <a href="{{ route('catalog.vehicle-types.create') }}">Add one now.</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($types->hasPages())
    <div class="card-body border-top py-3">
        {{ $types->links() }}
    </div>
    @endif
</div>

@include('partials.delete-modal')
@endsection
