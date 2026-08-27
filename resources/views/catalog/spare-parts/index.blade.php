@extends('layouts.app')
@section('title', 'Spare Parts')

@section('breadcrumb')
    <li class="breadcrumb-item active">Catalog</li>
    <li class="breadcrumb-item active">Spare Parts</li>
@endsection

@section('content')
@include('partials.page-header', [
    'title'    => 'Spare Parts',
    'subtitle' => 'All Bajaj spare parts inventory',
    'actions'  => [['label' => 'Add Part', 'route' => 'catalog.spare-parts.create', 'icon' => 'fa-plus']],
])

<!-- Filters -->
<div class="card mb-3">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end filter-form">
    <div class="col-12 col-md-4">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fa fa-search"></i></span>
            <input type="text" name="search" class="form-control live-search" placeholder="Part name, number, OEM…" value="{{ request('search') }}">
        </div>
    </div>
    <div class="col-auto">
        <select name="category" class="form-select form-select-sm ts-select">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <select name="stock_status" class="form-select form-select-sm ts-select">
            <option value="">All Stock</option>
            <option value="low" {{ request('stock_status') === 'low' ? 'selected' : '' }}>Low Stock</option>
            <option value="out" {{ request('stock_status') === 'out' ? 'selected' : '' }}>Out of Stock</option>
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
        @if(request()->hasAny(['search','category','stock_status','status']))
            <a href="{{ route('catalog.spare-parts.index') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="fa fa-xmark"></i></a>
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
            <th>Part</th>
            <th>Category</th>
            <th>Stock</th>
            <th>Reorder</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($parts as $part)
        <tr>
            <td class="text-muted">{{ $parts->firstItem() + $loop->index }}</td>
            <td>
                <a href="{{ route('catalog.spare-parts.show', $part) }}" class="fw-semibold text-dark text-decoration-none">
                    {{ $part->name }}
                </a>
                <div class="text-muted small">{{ $part->part_number }}
                    @if($part->oem_number) · OEM: {{ $part->oem_number }} @endif
                </div>
            </td>
            <td>
                <span class="badge" style="background:#f1f5f9;color:#475569;font-size:.72rem;padding:3px 8px;border-radius:5px">{{ $part->category->name }}</span>
            </td>
            <td>
                <span class="stock-pill {{ $part->stock_status === 'out_of_stock' ? 'out' : ($part->stock_status === 'low' ? 'low' : 'in-stock') }}">
                    {{ $part->current_stock }} {{ $part->unit->abbreviation }}
                </span>
            </td>
            <td class="text-muted">{{ $part->reorder_level }}</td>
            <td><span class="badge badge-status-{{ $part->status }}">{{ ucfirst($part->status) }}</span></td>
            <td class="text-end">
                <a href="{{ route('catalog.spare-parts.show', $part) }}" class="btn btn-action btn-outline-secondary me-1"><i class="fa fa-eye"></i></a>
                <a href="{{ route('catalog.spare-parts.edit', $part) }}" class="btn btn-action btn-outline-primary me-1"><i class="fa fa-pen"></i></a>
                <button class="btn btn-action btn-outline-danger"
                        data-delete-url="{{ route('catalog.spare-parts.destroy', $part) }}"
                        data-delete-message="Delete spare part '{{ $part->name }}'?">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center text-muted py-5">
                <i class="fa fa-gears fs-2 d-block mb-2 opacity-25"></i>
                No spare parts found.
                <a href="{{ route('catalog.spare-parts.create') }}">Add one now.</a>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>
@if($parts->hasPages())
<div class="card-body border-top py-3">{{ $parts->links() }}</div>
@endif
</div>

@include('partials.delete-modal')
@endsection
