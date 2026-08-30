@extends('layouts.app')
@section('title', 'Current Stock')
@section('breadcrumb')
    <li class="breadcrumb-item active">Inventory</li>
    <li class="breadcrumb-item active">Current Stock</li>
@endsection
@section('content')

@include('partials.page-header', [
    'title'    => 'Current Stock' . ($warehouseId ? ' — ' . ($warehouses->find($warehouseId)?->name ?? '') : ''),
    'subtitle' => $warehouseId ? 'Per-warehouse inventory snapshot' : 'Live snapshot of all inventory levels',
])

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary-soft"><i class="fa fa-boxes-stacked"></i></div>
            <div class="stat-body">
                <div class="stat-value">Br {{ number_format($summary['total_parts_value'] + $summary['total_vehicles_value'], 0) }}</div>
                <div class="stat-label">Total Inventory Value</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success-soft"><i class="fa fa-gears"></i></div>
            <div class="stat-body">
                <div class="stat-value">Br {{ number_format($summary['total_parts_value'], 0) }}</div>
                <div class="stat-label">Parts Stock Value</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning-soft"><i class="fa fa-motorcycle"></i></div>
            <div class="stat-body">
                <div class="stat-value">Br {{ number_format($summary['total_vehicles_value'], 0) }}</div>
                <div class="stat-label">Vehicles Stock Value</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-danger-soft"><i class="fa fa-triangle-exclamation"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $summary['low_parts'] + $summary['low_vehicles'] }}</div>
                <div class="stat-label">Low / Out of Stock</div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="stockTabs">
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'parts' ? 'active' : '' }}" href="?tab=parts{{ $warehouseId ? '&warehouse_id='.$warehouseId : '' }}">
            <i class="fa fa-gears me-1"></i>Spare Parts
            @if($summary['low_parts'] > 0)
                <span class="badge bg-warning text-dark ms-1">{{ $summary['low_parts'] }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'vehicles' ? 'active' : '' }}" href="?tab=vehicles{{ $warehouseId ? '&warehouse_id='.$warehouseId : '' }}">
            <i class="fa fa-motorcycle me-1"></i>Vehicles
            @if($summary['low_vehicles'] > 0)
                <span class="badge bg-warning text-dark ms-1">{{ $summary['low_vehicles'] }}</span>
            @endif
        </a>
    </li>
</ul>

<!-- Filters -->
<div class="card mb-3">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end filter-form">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="col-12 col-md-4">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fa fa-search"></i></span>
            <input type="text" name="search" class="form-control live-search"
                   placeholder="{{ $tab === 'parts' ? 'Part name or number…' : 'Model name or code…' }}"
                   value="{{ request('search') }}">
        </div>
    </div>
    <div class="col-auto">
        <select name="warehouse_id" class="form-select form-select-sm ts-select" style="min-width:140px">
            <option value="">All Warehouses</option>
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
            @endforeach
        </select>
    </div>
    @if($tab === 'parts')
    <div class="col-auto">
        <select name="vehicle_model" class="form-select form-select-sm ts-select" style="min-width:200px">
            <option value="">All Vehicle Models</option>
            @foreach($vehicleModels as $vm)
                <option value="{{ $vm->id }}" {{ request('vehicle_model') == $vm->id ? 'selected' : '' }}>
                    {{ $vm->brand }} {{ $vm->model_name }}{{ $vm->model_code ? ' ('.$vm->model_code.')' : '' }}
                </option>
            @endforeach
        </select>
    </div>
    @else
    <div class="col-auto">
        <select name="vehicle_type" class="form-select form-select-sm ts-select">
            <option value="">All Types</option>
            @foreach($vehicleTypes as $vt)
                <option value="{{ $vt->id }}" {{ request('vehicle_type') == $vt->id ? 'selected' : '' }}>{{ $vt->name }}</option>
            @endforeach
        </select>
    </div>
    @endif
    <div class="col-auto">
        <select name="stock_filter" class="form-select form-select-sm ts-select">
            <option value="">All Stock</option>
            <option value="ok"  {{ request('stock_filter') === 'ok'  ? 'selected' : '' }}>In Stock</option>
            <option value="low" {{ request('stock_filter') === 'low' ? 'selected' : '' }}>Low Stock</option>
            <option value="out" {{ request('stock_filter') === 'out' ? 'selected' : '' }}>Out of Stock</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-filter me-1"></i>Filter</button>
        @if(request()->hasAny(['search','vehicle_model','vehicle_type','stock_filter','warehouse_id']))
            <a href="?tab={{ $tab }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="fa fa-xmark"></i></a>
        @endif
    </div>
</form>
</div>
</div>

@if($tab === 'parts')
<!-- SPARE PARTS TABLE -->
<div class="card">
<div class="table-responsive">
<table class="table">
    <thead>
        <tr>
            <th>Part</th>
            <th style="width:160px">Vehicle Models</th>
            <th>Unit</th>
            <th>Current Stock (Unsold)</th>
            <th>Reorder Level</th>
            <th>Status</th>
            <th>Stock Value</th>
        </tr>
    </thead>
    <tbody>
        @forelse($parts as $part)
        @php
            $unsoldQty = $part->unsold_qty ?? 0;
            $reorder   = $part->reorder_level ?? 0;
            $isOut = $unsoldQty <= 0;
            $isLow = !$isOut && $unsoldQty <= $reorder;
        @endphp
        <tr>
            <td>
                @if($isWarehouseView)
                    <div class="fw-semibold">{{ $part->name }}</div>
                    <div class="text-muted" style="font-size:.75rem">{{ $part->part_number }}</div>
                @else
                    <a href="{{ route('catalog.spare-parts.show', $part) }}" class="fw-semibold text-dark text-decoration-none">
                        {{ $part->name }}
                    </a>
                    <div class="text-muted" style="font-size:.75rem">{{ $part->part_number }}</div>
                @endif
            </td>
            <td style="max-width:160px">
                @php
                    $vlist = $isWarehouseView
                        ? ($part->vehicle_model_list ?? collect())
                        : $part->compatibleVehicles->map(fn($v) => $v->brand.' '.$v->model_name);

                    // If a vehicle model filter is active, put that model first
                    $filteredVmId = request('vehicle_model');
                    if ($filteredVmId && !$isWarehouseView) {
                        $filteredVm = $part->compatibleVehicles->firstWhere('id', $filteredVmId);
                        if ($filteredVm) {
                            $filteredName = $filteredVm->brand.' '.$filteredVm->model_name;
                            $vlist = collect([$filteredName])->merge($vlist->reject(fn($n) => $n === $filteredName));
                        }
                    }

                    $vcount      = $vlist->count();
                    $partNameStr = addslashes($part->name);
                @endphp
                @if($vcount === 0)
                    <span class="text-muted small">—</span>
                @else
                    <div style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:150px;font-size:.75rem;color:#3730a3">
                        {{ $vlist->first() }}@if($vcount > 1) &hellip;@endif
                    </div>
                    @if($vcount > 1)
                        <span class="text-muted" style="font-size:.68rem;cursor:pointer;text-decoration:underline"
                              onclick="openCsVehicleModal('{{ $partNameStr }}', '{{ addslashes($vlist->join('||')) }}')">
                            +{{ $vcount - 1 }} more
                        </span>
                    @endif
                @endif
            </td>
            <td class="text-muted small">{{ $isWarehouseView ? $part->unit_abbr : $part->unit->abbreviation }}</td>
            <td>
                <span class="fw-bold fs-6 {{ $isOut ? 'text-danger' : ($isLow ? 'text-warning' : 'text-success') }}">
                    {{ $unsoldQty }}
                </span>
            </td>
            <td class="text-muted">{{ $reorder }}</td>
            <td>
                <span class="stock-pill {{ $isOut ? 'out' : ($isLow ? 'low' : 'in-stock') }}">
                    {{ $isOut ? 'Out of Stock' : ($isLow ? 'Low Stock' : 'In Stock') }}
                </span>
            </td>
            <td class="text-muted small">
                @php $sv = $part->stock_value ?? 0; @endphp
                {{ $sv > 0 ? 'Br '.number_format($sv, 2) : '—' }}
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted py-5">No parts found.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
@if($parts->hasPages())
<div class="card-body border-top py-3">{{ $parts->links() }}</div>
@endif
</div>

@else
<!-- VEHICLES TABLE -->
<div class="card">
<div class="table-responsive">
<table class="table">
    <thead>
        <tr>
            <th>Vehicle Model</th>
            <th>Type</th>
            <th>Current Stock (Unsold)</th>
            <th>Reorder Level</th>
            <th>Status</th>
            <th>Stock Value</th>
        </tr>
    </thead>
    <tbody>
        @forelse($vehicles as $vs)
        @php
            $isEloquent = isset($vs->vehicleModel);
            $vmName  = $isEloquent ? ($vs->vehicleModel->brand.' '.$vs->vehicleModel->model_name) : ($vs->brand.' '.$vs->model_name);
            $vmCode  = $isEloquent ? $vs->vehicleModel->model_code : $vs->model_code;
            $vmType  = $isEloquent ? $vs->vehicleModel->vehicleType->name : $vs->type_name;
            $vmStockValue = $vs->stock_value ?? 0;
            $unsoldQty = $vs->unsold_qty ?? 0;
            $reorder   = $vs->reorder_level ?? 0;
            $isOut   = $unsoldQty <= 0;
            $isLow   = !$isOut && $unsoldQty <= $reorder;
        @endphp
        <tr>
            <td>
                @if($isEloquent)
                    <a href="{{ route('catalog.vehicle-models.show', $vs->vehicleModel) }}" class="fw-semibold text-dark text-decoration-none">
                        {{ $vmName }}
                    </a>
                @else
                    <div class="fw-semibold">{{ $vmName }}</div>
                @endif
                @if($vmCode)<div class="text-muted" style="font-size:.75rem">{{ $vmCode }}</div>@endif
            </td>
            <td>
                <span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:.72rem;padding:3px 8px;border-radius:5px">{{ $vmType }}</span>
            </td>
            <td>
                <span class="fw-bold fs-6 {{ $isOut ? 'text-danger' : ($isLow ? 'text-warning' : 'text-success') }}">
                    {{ $unsoldQty }}
                </span>
            </td>
            <td class="text-muted">{{ $reorder }}</td>
            <td>
                <span class="stock-pill {{ $isOut ? 'out' : ($isLow ? 'low' : 'in-stock') }}">
                    {{ $isOut ? 'Out of Stock' : ($isLow ? 'Low Stock' : 'In Stock') }}
                </span>
            </td>
            <td class="text-muted small">{{ $vmStockValue > 0 ? 'Br '.number_format($vmStockValue, 2) : '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted py-5">No vehicles found.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
@if($vehicles->hasPages())
<div class="card-body border-top py-3">{{ $vehicles->links() }}</div>
@endif
</div>
@endif


{{-- Vehicle Models Modal --}}
<div class="modal fade" id="csVehicleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="fa fa-motorcycle me-2 text-primary"></i><span id="csVehModalPartName"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Compatible vehicle models:</p>
                <div id="csVehModalList" class="d-flex flex-wrap gap-2"></div>
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
function openCsVehicleModal(partName, vehicles) {
    document.getElementById('csVehModalPartName').textContent = partName;
    const list = document.getElementById('csVehModalList');
    list.innerHTML = '';
    vehicles.split('||').forEach(function(v) {
        if (!v.trim()) return;
        const badge = document.createElement('span');
        badge.className = 'badge';
        badge.style = 'background:#e0e7ff;color:#3730a3;font-size:.8rem;padding:5px 10px;border-radius:6px';
        badge.textContent = v.trim();
        list.appendChild(badge);
    });
    new bootstrap.Modal(document.getElementById('csVehicleModal')).show();
}
</script>
@endpush