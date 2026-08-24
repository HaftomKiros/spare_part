@extends('layouts.app')
@section('title', 'Units of Measure')
@section('breadcrumb')
    <li class="breadcrumb-item active">Catalog</li>
    <li class="breadcrumb-item active">Units</li>
@endsection
@section('content')
@include('partials.page-header', [
    'title'    => 'Units of Measure',
    'subtitle' => 'Manage measurement units used for spare parts',
    'actions'  => [['label' => 'Add Unit', 'route' => 'catalog.units.create', 'icon' => 'fa-plus']],
])
@include('partials.filter-bar', ['searchPlaceholder' => 'Search units…'])

<div class="card">
<div class="table-responsive">
<table class="table">
    <thead>
        <tr><th>#</th><th>Name</th><th>Abbreviation</th><th>Description</th><th>Parts Using</th><th class="text-end">Actions</th></tr>
    </thead>
    <tbody>
        @forelse($units as $unit)
        <tr>
            <td class="text-muted">{{ $units->firstItem() + $loop->index }}</td>
            <td class="fw-semibold">{{ $unit->name }}</td>
            <td><span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:.72rem;padding:3px 8px;border-radius:5px">{{ $unit->abbreviation }}</span></td>
            <td class="text-muted">{{ $unit->description ?? '—' }}</td>
            <td>
                <a href="{{ route('catalog.spare-parts.index') }}" class="text-primary">{{ $unit->spare_parts_count }}</a>
            </td>
            <td class="text-end">
                <a href="{{ route('catalog.units.edit', $unit) }}" class="btn btn-action btn-outline-primary me-1"><i class="fa fa-pen"></i></a>
                <button class="btn btn-action btn-outline-danger"
                        data-delete-url="{{ route('catalog.units.destroy', $unit) }}"
                        data-delete-message="Delete unit '{{ $unit->name }}'?">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center text-muted py-5">
                <i class="fa fa-ruler fs-2 d-block mb-2 opacity-25"></i>
                No units found. <a href="{{ route('catalog.units.create') }}">Add one now.</a>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>
@if($units->hasPages())<div class="card-body border-top py-3">{{ $units->links() }}</div>@endif
</div>
@include('partials.delete-modal')
@endsection
