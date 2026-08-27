@extends('layouts.app')
@section('title', 'Stock Transfers')
@section('breadcrumb')
    <li class="breadcrumb-item active">Inventory</li>
    <li class="breadcrumb-item active">Stock Transfer</li>
@endsection
@section('content')
@include('partials.page-header', [
    'title'    => 'Stock Transfers',
    'subtitle' => 'History of stock movements between warehouses',
    'actions'  => [['label' => 'New Transfer', 'route' => 'inventory.transfers.create', 'icon' => 'fa-right-left', 'class' => 'btn-warning']],
])

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fa fa-circle-xmark me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Filters --}}
<div class="card mb-3">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-auto">
        <select name="warehouse_id" class="form-select form-select-sm ts-select" style="min-width:160px">
            <option value="">All Warehouses</option>
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
    </div>
    <div class="col-auto">
        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-filter me-1"></i>Filter</button>
        @if(request()->hasAny(['warehouse_id','date_from','date_to']))
            <a href="{{ route('inventory.transfers.index') }}" class="btn btn-sm btn-outline-secondary ms-1">
                <i class="fa fa-xmark"></i>
            </a>
        @endif
    </div>
</form>
</div>
</div>

{{-- Transfer cards --}}
@forelse($transfers as $transfer)
@php
    $lines     = $itemLines[$transfer->id] ?? collect();
    $totalQty  = $lines->sum('quantity');
    $totalCost = $lines->sum(fn($l) => $l->quantity * $l->unit_price);
    $soldQty   = $lines->sum('total_sold');
    $remaining = $totalQty - $soldQty;
@endphp
<div class="card mb-3 transfer-card">
    {{-- Card header: route + meta --}}
    <div class="card-header d-flex flex-wrap align-items-center gap-2 py-2">
        <div class="d-flex align-items-center gap-2 flex-grow-1">
            <span class="badge bg-warning-subtle text-warning-emphasis fw-bold" style="font-size:.78rem">
                <i class="fa fa-right-left me-1"></i>{{ $transfer->transfer_number }}
            </span>
            <span class="fw-semibold text-dark" style="font-size:.88rem">
                {{ $transfer->fromWarehouse?->name ?? '—' }}
            </span>
            <i class="fa fa-arrow-right text-warning" style="font-size:.8rem"></i>
            <span class="fw-semibold text-dark" style="font-size:.88rem">
                {{ $transfer->toWarehouse?->name ?? '—' }}
            </span>
        </div>
        <div class="d-flex align-items-center gap-3 text-muted" style="font-size:.78rem">
            <span><i class="fa fa-user me-1"></i>{{ $transfer->user?->name ?? '—' }}</span>
            <span><i class="fa fa-clock me-1"></i>{{ $transfer->transferred_at?->format('M d, Y H:i') }}</span>
            @if($transfer->notes)
            <span class="d-none d-md-inline"><i class="fa fa-note-sticky me-1"></i>{{ $transfer->notes }}</span>
            @endif
        </div>
    </div>

    {{-- Summary pills --}}
    <div class="px-3 py-2 border-bottom d-flex flex-wrap gap-3" style="background:#f8f9ff">
        <div class="d-flex align-items-center gap-1" style="font-size:.8rem">
            <span class="text-muted">Items:</span>
            <span class="fw-bold text-dark">{{ $lines->count() }}</span>
        </div>
        <div class="d-flex align-items-center gap-1" style="font-size:.8rem">
            <span class="text-muted">Total Qty:</span>
            <span class="fw-bold text-dark">{{ $totalQty }}</span>
        </div>
        <div class="d-flex align-items-center gap-1" style="font-size:.8rem">
            <span class="text-muted">Cost Value:</span>
            <span class="fw-bold" style="color:var(--brand-1)">Br {{ number_format($totalCost, 2) }}</span>
        </div>
        <div class="d-flex align-items-center gap-1" style="font-size:.8rem">
            <span class="text-muted">Sold at dest:</span>
            <span class="fw-bold text-danger">{{ $soldQty }}</span>
        </div>
        <div class="d-flex align-items-center gap-1" style="font-size:.8rem">
            <span class="text-muted">Remaining:</span>
            <span class="fw-bold text-success">{{ $remaining }}</span>
        </div>
    </div>

    {{-- Item lines table --}}
    @if($lines->count())
    <div class="table-responsive">
    <table class="table table-sm mb-0" style="font-size:.83rem">
        <thead style="background:#f1f5f9">
            <tr>
                <th class="ps-3">Item</th>
                <th>Type</th>
                <th class="text-center">Transferred</th>
                <th class="text-center">Sold</th>
                <th class="text-center">Remaining</th>
                <th class="text-end">Unit Cost</th>
                <th class="text-end pe-3">Line Value</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lines as $line)
            @php
                $itemName = $line->item_type === 'spare_part'
                    ? ($line->part_name ?? '—')
                    : trim(($line->brand ?? '') . ' ' . ($line->model_name ?? '') . ($line->model_code ? ' ('.$line->model_code.')' : ''));
                $lineRemaining = $line->quantity - $line->total_sold;
            @endphp
            <tr>
                <td class="ps-3">
                    <div class="fw-semibold">{{ $itemName }}</div>
                    @if($line->item_type === 'spare_part' && $line->part_number)
                        <div class="text-muted" style="font-size:.7rem">{{ $line->part_number }}</div>
                    @endif
                </td>
                <td>
                    <span class="badge bg-{{ $line->item_type === 'vehicle' ? 'primary' : 'success' }}-subtle
                                       text-{{ $line->item_type === 'vehicle' ? 'primary' : 'success' }}-emphasis"
                          style="font-size:.7rem">
                        {{ $line->item_type === 'vehicle' ? 'Vehicle' : 'Spare Part' }}
                    </span>
                </td>
                <td class="text-center fw-semibold">{{ $line->quantity }}</td>
                <td class="text-center text-danger">{{ $line->total_sold }}</td>
                <td class="text-center">
                    <span class="badge {{ $lineRemaining > 0 ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis' }}">
                        {{ $lineRemaining }}
                    </span>
                </td>
                <td class="text-end text-muted">Br {{ number_format($line->unit_price, 2) }}</td>
                <td class="text-end fw-semibold pe-3" style="color:var(--brand-1)">
                    Br {{ number_format($line->quantity * $line->unit_price, 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @else
    <div class="px-3 py-3 text-muted small"><i class="fa fa-circle-info me-1"></i>No item details available.</div>
    @endif
</div>
@empty
<div class="card">
<div class="card-body text-center py-5 text-muted">
    <i class="fa fa-right-left fs-2 d-block mb-2 opacity-25"></i>
    <p class="mb-1 fw-semibold">No transfer records found.</p>
    <a href="{{ route('inventory.transfers.create') }}" class="btn btn-sm btn-warning mt-2">
        <i class="fa fa-plus me-1"></i>Create your first transfer
    </a>
</div>
</div>
@endforelse

@if($transfers->hasPages())
<div class="d-flex justify-content-center mt-3">{{ $transfers->links() }}</div>
@endif

@endsection
