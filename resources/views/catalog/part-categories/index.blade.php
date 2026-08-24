@extends('layouts.app')
@section('title', 'Part Categories')

@section('breadcrumb')
    <li class="breadcrumb-item active">Catalog</li>
    <li class="breadcrumb-item active">Part Categories</li>
@endsection

@section('content')
@include('partials.page-header', [
    'title'    => 'Part Categories',
    'subtitle' => 'Organise spare parts into logical groups',
    'actions'  => [['label' => 'Add Category', 'route' => 'catalog.part-categories.create', 'icon' => 'fa-plus']],
])

@include('partials.filter-bar', ['searchPlaceholder' => 'Search categories…'])

<div class="card">
<div class="table-responsive">
<table class="table">
    <thead>
        <tr>
            <th>#</th>
            <th>Category</th>
            <th>Parent</th>
            <th>Icon</th>
            <th>Parts</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($categories as $cat)
        <tr>
            <td class="text-muted">{{ $categories->firstItem() + $loop->index }}</td>
            <td>
                <div class="fw-semibold">{{ $cat->name }}</div>
                @if($cat->description)
                    <div class="text-muted small">{{ Str::limit($cat->description, 60) }}</div>
                @endif
            </td>
            <td class="text-muted">{{ $cat->parent?->name ?? '— Root —' }}</td>
            <td>
                @if($cat->icon)
                    <i class="fa {{ $cat->icon }} text-muted"></i>
                @else
                    <span class="text-muted">—</span>
                @endif
            </td>
            <td>
                <a href="{{ route('catalog.spare-parts.index', ['category' => $cat->id]) }}" class="text-primary">
                    {{ $cat->spare_parts_count }}
                </a>
            </td>
            <td><span class="badge badge-status-{{ $cat->status }}">{{ ucfirst($cat->status) }}</span></td>
            <td class="text-end">
                <a href="{{ route('catalog.part-categories.edit', $cat) }}" class="btn btn-action btn-outline-primary me-1">
                    <i class="fa fa-pen"></i>
                </a>
                <button class="btn btn-action btn-outline-danger"
                        data-delete-url="{{ route('catalog.part-categories.destroy', $cat) }}"
                        data-delete-message="Delete category '{{ $cat->name }}'?">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center text-muted py-5">
                <i class="fa fa-layer-group fs-2 d-block mb-2 opacity-25"></i>
                No categories found. <a href="{{ route('catalog.part-categories.create') }}">Add one now.</a>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>
@if($categories->hasPages())
<div class="card-body border-top py-3">{{ $categories->links() }}</div>
@endif
</div>

@include('partials.delete-modal')
@endsection
