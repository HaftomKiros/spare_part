@extends('layouts.app')
@section('title', 'Stock Transfers')
@section('breadcrumb')
    <li class="breadcrumb-item active">Inventory</li>
    <li class="breadcrumb-item active">Stock Transfer</li>
@endsection
@section('content')
@include('partials.page-header', [
    'title'    => 'Stock Transfer',
    'subtitle' => 'Move stock between warehouses',
    'actions'  => [['label' => 'New Transfer', 'route' => 'inventory.transfers.create', 'icon' => 'fa-right-left', 'class' => 'btn-warning']],
])

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Filters -->
<div class="card mb-3">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end filter-form">
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
            <a href="{{ route('inventory.transfers.index') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="fa fa-xmark"></i></a>
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
            <th>Date & Time</th>
            <th>Item</th>
            <th>Direction</th>
            <th>Warehouse</th>
            <th>Qty</th>
            <th>Before</th>
            <th>After</th>
            <th>By</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
        @forelse($movements as $mv)
        <tr>
            <td class="text-muted small">{{ \Carbon\Carbon::parse($mv->created_at)->format('M d, Y H:i') }}</td>
            <td>
                <div class="fw-semibold small">
                    {{ $mv->item_type === 'spare_part' ? ($mv->part_name ?? '—') : (($mv->brand ?? '').' '.($mv->model_name ?? '')) }}
                </div>
                @if($mv->item_type === 'spare_part' && $mv->part_number)
                    <div class="text-muted" style="font-size:.72rem">{{ $mv->part_number }}</div>
                @endif
            </td>
            <td>
                @if($mv->movement_type === 'adjustment_out')
                    <span class="text-danger"><i class="fa fa-arrow-up me-1"></i>OUT</span>
                @else
                    <span class="text-success"><i class="fa fa-arrow-down me-1"></i>IN</span>
                @endif
            </td>
            <td class="small">{{ $mv->warehouse_name }}</td>
            <td class="fw-bold {{ $mv->movement_type === 'adjustment_out' ? 'text-danger' : 'text-success' }}">
                {{ $mv->movement_type === 'adjustment_out' ? '-' : '+' }}{{ $mv->quantity }}
            </td>
            <td class="text-muted">{{ $mv->quantity_before }}</td>
            <td class="fw-semibold">{{ $mv->quantity_after }}</td>
            <td class="small">{{ $mv->user_name }}</td>
            <td class="text-muted small">{{ $mv->notes ?? '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="9" class="text-center text-muted py-5">
                <i class="fa fa-right-left fs-2 d-block mb-2 opacity-25"></i>
                No transfer records found.
                <a href="{{ route('inventory.transfers.create') }}">Create your first transfer.</a>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>
@if($movements->hasPages())
<div class="card-body border-top py-3">{{ $movements->links() }}</div>
@endif
</div>
@endsection
