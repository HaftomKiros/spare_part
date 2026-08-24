@extends('layouts.app')
@section('title', 'Adjustment ' . $adjustment->adjustment_number)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('inventory.adjustments.index') }}">Adjustments</a></li>
    <li class="breadcrumb-item active">{{ $adjustment->adjustment_number }}</li>
@endsection
@section('content')
@include('partials.page-header', [
    'title'    => 'Stock Adjustment',
    'subtitle' => $adjustment->adjustment_number,
])

<div class="row g-3">
<div class="col-12 col-md-4">
<div class="card">
<div class="card-header"><i class="fa fa-info-circle me-2 text-primary"></i>Details</div>
<div class="card-body">
    <table class="table table-sm table-borderless mb-0 small">
        <tr><th class="text-muted fw-normal">Number</th><td class="fw-bold">{{ $adjustment->adjustment_number }}</td></tr>
        <tr><th class="text-muted fw-normal">Date</th><td>{{ $adjustment->adjustment_date->format('M d, Y') }}</td></tr>
        <tr><th class="text-muted fw-normal">Type</th>
            <td>
                <span class="badge bg-{{ $adjustment->adjustment_type === 'increase' ? 'success' : ($adjustment->adjustment_type === 'decrease' ? 'danger' : 'warning') }}">
                    {{ ucfirst($adjustment->adjustment_type) }}
                </span>
            </td>
        </tr>
        <tr><th class="text-muted fw-normal">Status</th>
            <td><span class="badge bg-{{ $adjustment->status_badge }}">{{ ucfirst($adjustment->status) }}</span></td>
        </tr>
        <tr><th class="text-muted fw-normal">Created by</th><td>{{ $adjustment->user->name }}</td></tr>
        <tr><th class="text-muted fw-normal">Reason</th><td>{{ $adjustment->reason }}</td></tr>
    </table>
</div>
</div>
</div>

<div class="col-12 col-md-8">
<div class="card">
<div class="card-header"><i class="fa fa-list me-2 text-primary"></i>Adjusted Items ({{ $adjustment->items->count() }})</div>
<div class="table-responsive">
<table class="table">
    <thead>
        <tr>
            <th>Item</th>
            <th>Type</th>
            <th>Before</th>
            <th>Adjusted</th>
            <th>After</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
        @foreach($adjustment->items as $item)
        <tr>
            <td>
                <div class="fw-semibold small">{{ $item->item_name }}</div>
                <div class="text-muted" style="font-size:.72rem">
                    {{ $item->item_type === 'vehicle' ? $item->vehicleModel?->vehicleType?->name : $item->sparePart?->part_number }}
                </div>
            </td>
            <td>
                <span class="badge bg-{{ $item->item_type === 'vehicle' ? 'primary' : 'success' }} bg-opacity-15 text-{{ $item->item_type === 'vehicle' ? 'primary' : 'success' }}">
                    {{ $item->item_type === 'vehicle' ? 'Vehicle' : 'Part' }}
                </span>
            </td>
            <td>{{ $item->quantity_before }}</td>
            <td class="fw-bold {{ $item->quantity_adjusted > 0 ? 'text-success' : 'text-danger' }}">
                {{ $item->quantity_adjusted > 0 ? '+' : '' }}{{ $item->quantity_adjusted }}
            </td>
            <td class="fw-semibold">{{ $item->quantity_after }}</td>
            <td class="text-muted small">{{ $item->notes ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>
</div>
</div>
</div>
@endsection
