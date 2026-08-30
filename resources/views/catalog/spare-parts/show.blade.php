@extends('layouts.app')
@section('title', $sparePart->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.spare-parts.index') }}">Spare Parts</a></li>
    <li class="breadcrumb-item active">{{ $sparePart->part_number }}</li>
@endsection
@section('content')

{{-- ── Page Header ──────────────────────────────────────────────────── --}}
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h4 class="fw-bold mb-1" style="color:#1e293b">{{ $sparePart->name }}</h4>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:.78rem;padding:4px 10px">{{ $sparePart->part_number }}</span>
            @if($sparePart->oem_number)
            <span class="text-muted small">OEM: {{ $sparePart->oem_number }}</span>
            @endif
            <span class="badge badge-status-{{ $sparePart->status }}">{{ ucfirst($sparePart->status) }}</span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('catalog.spare-parts.edit', $sparePart) }}" class="btn btn-outline-primary btn-sm">
            <i class="fa fa-pen me-1"></i>Edit Part
        </a>
    </div>
</div>

<div class="row g-3">

{{-- ── Left: Details + Pricing ─────────────────────────────────────── --}}
<div class="col-12 col-lg-4">

    {{-- Details card --}}
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="fa fa-circle-info text-primary"></i><span class="fw-semibold">Part Details</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-borderless mb-0 small">
                <tr class="border-bottom">
                    <td class="text-muted ps-3 py-2" style="width:40%">Part #</td>
                    <td class="fw-semibold py-2">{{ $sparePart->part_number }}</td>
                </tr>
                @if($sparePart->oem_number)
                <tr class="border-bottom">
                    <td class="text-muted ps-3 py-2">OEM #</td>
                    <td class="py-2">{{ $sparePart->oem_number }}</td>
                </tr>
                @endif
                <tr class="border-bottom">
                    <td class="text-muted ps-3 py-2">Unit</td>
                    <td class="py-2">{{ $sparePart->unit->name }} <span class="text-muted">({{ $sparePart->unit->abbreviation }})</span></td>
                </tr>
                <tr class="border-bottom">
                    <td class="text-muted ps-3 py-2">Shelf</td>
                    <td class="py-2">{{ $sparePart->location ?? '—' }}</td>
                </tr>
                @if($sparePart->description)
                <tr>
                    <td class="text-muted ps-3 py-2">Description</td>
                    <td class="py-2 text-muted small">{{ $sparePart->description }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    {{-- Pricing card --}}
    <div class="card">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="fa fa-tag text-primary"></i><span class="fw-semibold">Pricing</span>
        </div>
        <div class="card-body">
            <div class="row g-2 text-center">
                @if($sparePart->buying_price > 0)
                <div class="col-4">
                    <div class="p-2 rounded-3" style="background:#fff7ed">
                        <div class="fw-bold" style="color:#c2410c;font-size:.95rem">Br {{ number_format($sparePart->buying_price,2) }}</div>
                        <div class="text-muted" style="font-size:.68rem;margin-top:2px">Purchase</div>
                    </div>
                </div>
                <div class="col-4">
                @else
                <div class="col-6">
                @endif
                    <div class="p-2 rounded-3" style="background:#f0fdf4">
                        <div class="fw-bold" style="color:#15803d;font-size:.95rem">Br {{ number_format($sparePart->selling_price_min,2) }}</div>
                        <div class="text-muted" style="font-size:.68rem;margin-top:2px">Min Price</div>
                    </div>
                </div>
                @if($sparePart->buying_price > 0)
                <div class="col-4">
                @else
                <div class="col-6">
                @endif
                    <div class="p-2 rounded-3" style="background:#eff6ff">
                        <div class="fw-bold" style="color:#1d4ed8;font-size:.95rem">Br {{ number_format($sparePart->selling_price_max,2) }}</div>
                        <div class="text-muted" style="font-size:.68rem;margin-top:2px">Max Price</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ── Center: Stock status ────────────────────────────────────────── --}}
<div class="col-12 col-lg-4">
    <div class="card h-100">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="fa fa-warehouse text-primary"></i><span class="fw-semibold">Stock Status</span>
        </div>
        <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4">
            <div style="width:90px;height:90px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:12px;
                background:{{ $unsoldStock <= 0 ? '#fee2e2' : ($unsoldStock <= $sparePart->reorder_level ? '#fef3c7' : '#dcfce7') }}">
                <span class="fw-bold" style="font-size:2rem;color:{{ $unsoldStock <= 0 ? '#dc2626' : ($unsoldStock <= $sparePart->reorder_level ? '#d97706' : '#16a34a') }}">
                    {{ $unsoldStock }}
                </span>
            </div>
            <div class="text-muted mb-2">{{ $sparePart->unit->name }}(s) in stock</div>
            <span class="stock-pill {{ $unsoldStock <= 0 ? 'out' : ($unsoldStock <= $sparePart->reorder_level ? 'low' : 'in-stock') }} mb-3">
                {{ $unsoldStock <= 0 ? 'Out of Stock' : ($unsoldStock <= $sparePart->reorder_level ? 'Low Stock' : 'In Stock') }}
            </span>
            <div class="w-100 p-2 rounded-3 mb-3" style="background:#f8fafc;border:1px dashed #e2e8f0">
                <div class="row text-center g-0">
                    <div class="col-6 border-end">
                        <div class="fw-semibold">{{ $sparePart->current_stock }}</div>
                        <div class="text-muted" style="font-size:.7rem">Total Stock</div>
                    </div>
                    <div class="col-6">
                        <div class="fw-semibold">{{ $sparePart->reorder_level }}</div>
                        <div class="text-muted" style="font-size:.7rem">Reorder Level</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Right: Vehicle Models ───────────────────────────────────────── --}}
<div class="col-12 col-lg-4">
    <div class="card h-100">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="fa fa-motorcycle text-primary"></i>
            <span class="fw-semibold">Vehicle Models</span>
            <span class="badge bg-primary-subtle text-primary ms-auto" style="font-size:.72rem">{{ $sparePart->compatibleVehicles->count() }}</span>
        </div>
        <div class="card-body p-0" style="max-height:280px;overflow-y:auto">
            @forelse($sparePart->compatibleVehicles as $v)
            <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="transition:background .15s" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                <div style="width:32px;height:32px;border-radius:50%;background:#e0e7ff;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="fa fa-motorcycle" style="color:#6366f1;font-size:.75rem"></i>
                </div>
                <div>
                    <div class="fw-semibold small">{{ $v->brand }} {{ $v->model_name }}{{ $v->model_code ? ' ('.$v->model_code.')' : '' }}</div>
                    <div class="text-muted" style="font-size:.7rem">{{ $v->vehicleType->name }}</div>
                </div>
            </div>
            @empty
            <div class="text-center py-4">
                <i class="fa fa-motorcycle text-muted opacity-25 fa-2x mb-2 d-block"></i>
                <span class="text-muted small">No vehicles linked</span>
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- ── Recent Stock Movements ──────────────────────────────────────── --}}
<div class="col-12">
<div class="card">
<div class="card-header d-flex align-items-center gap-2">
    <i class="fa fa-clock-rotate-left text-primary"></i>
    <span class="fw-semibold">Recent Stock Movements</span>
    <span class="badge bg-secondary-subtle text-secondary ms-auto" style="font-size:.72rem">{{ $recentMovements->count() }} records</span>
</div>
<div class="table-responsive">
<table class="table mb-0">
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
            <td class="text-muted small">{{ $mv->quantity_before }}</td>
            <td class="text-muted small">{{ $mv->quantity_after }}</td>
            <td class="text-muted small">Br {{ number_format($mv->unit_cost, 2) }}</td>
            <td class="text-muted small">{{ $mv->reference_type ? class_basename($mv->reference_type).' #'.$mv->reference_id : '—' }}</td>
            <td class="fw-semibold small">{{ $mv->user->name }}</td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center text-muted py-4">
            <i class="fa fa-clock-rotate-left fa-2x d-block mb-2 opacity-25"></i>No movements recorded.
        </td></tr>
        @endforelse
    </tbody>
</table>
</div>
</div>
</div>

</div>
@endsection
