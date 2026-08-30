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
            <th style="width:140px">Vehicle Models</th>
            <th>Stock</th>
            <th>Reorder</th>
            <th>Sell Price</th>
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
            <td style="max-width:140px">
                @php $vc = $part->compatibleVehicles; @endphp
                @if($vc->count() === 0)
                    <span class="text-muted small">—</span>
                @else
                    <div style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:130px;font-size:.75rem;color:#3730a3;cursor:pointer"
                         @if($vc->count() > 2)
                         onclick="openVehicleModal('{{ addslashes($part->name) }}', {{ $part->id }})"
                         title="Click to see all {{ $vc->count() }} models"
                         @endif
                    >
                        {{ $vc->first()->brand }} {{ $vc->first()->model_name }}@if($vc->count() > 1) &hellip;@endif
                    </div>
                    @if($vc->count() > 2)
                        <span class="text-muted" style="font-size:.68rem;cursor:pointer"
                              onclick="openVehicleModal('{{ addslashes($part->name) }}', {{ $part->id }})">
                            +{{ $vc->count() - 1 }} more
                        </span>
                    @endif
                    {{-- hidden data for modal --}}
                    <span class="d-none veh-data" data-id="{{ $part->id }}"
                          data-name="{{ addslashes($part->name) }}"
                          data-vehicles="{{ addslashes($vc->map(fn($v)=>$v->brand.' '.$v->model_name.($v->model_code?' ('.$v->model_code.')':'')).implode('||')) }}">
                    </span>
                @endif
            </td>
            <td>
                <span class="stock-pill {{ $part->stock_status === 'out_of_stock' ? 'out' : ($part->stock_status === 'low' ? 'low' : 'in-stock') }}">
                    {{ $part->current_stock }} {{ $part->unit->abbreviation }}
                </span>
            </td>
            <td class="text-muted">{{ $part->reorder_level }}</td>
            <td class="small">
                @if($part->selling_price_min > 0 || $part->selling_price_max > 0)
                    <span class="text-muted">Br {{ number_format($part->selling_price_min, 2) }}</span>
                    <span class="text-muted mx-1">—</span>
                    <span class="fw-semibold text-success">Br {{ number_format($part->selling_price_max, 2) }}</span>
                @else
                    <span class="text-muted">—</span>
                @endif
            </td>
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
            <td colspan="9" class="text-center text-muted py-5">
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

{{-- Vehicle Models Modal --}}
<div class="modal fade" id="vehicleModelsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="fa fa-motorcycle me-2 text-primary"></i><span id="vehModalPartName"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Compatible vehicle models:</p>
                <div id="vehModalList" class="d-flex flex-wrap gap-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
function openVehicleModal(partName, partId) {
    const data = document.querySelector('.veh-data[data-id="' + partId + '"]');
    if (!data) return;
    document.getElementById('vehModalPartName').textContent = partName;
    const list = document.getElementById('vehModalList');
    list.innerHTML = '';
    data.dataset.vehicles.split('||').forEach(function(v) {
        if (!v.trim()) return;
        const badge = document.createElement('span');
        badge.className = 'badge';
        badge.style = 'background:#e0e7ff;color:#3730a3;font-size:.8rem;padding:5px 10px;border-radius:6px';
        badge.textContent = v.trim();
        list.appendChild(badge);
    });
    new bootstrap.Modal(document.getElementById('vehicleModelsModal')).show();
}
</script>
@endpush
