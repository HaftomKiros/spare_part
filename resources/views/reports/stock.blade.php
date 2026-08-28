@extends('layouts.app')
@section('title','Stock Report')
@section('breadcrumb')
    <li class="breadcrumb-item active">Reports</li>
    <li class="breadcrumb-item active">Stock</li>
@endsection
@section('content')

@include('partials.report-nav', ['active' => 'stock'])

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0" style="color:#1e293b">Stock Valuation Report</h5>
        <div class="text-muted small">Current inventory value breakdown</div>
    </div>
</div>

<div class="rpt-filter-card">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
        @include('partials.warehouse-filter')
        <div class="mt-auto"><button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-filter me-1"></i>Apply</button></div>
        @if($warehouseId)
        <div class="mt-auto ms-auto">
            <span class="rpt-period-badge"><i class="fa fa-warehouse"></i>{{ $warehouses->find($warehouseId)?->name }}</span>
        </div>
        @endif
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon bg-primary-soft"><i class="fa fa-gears"></i></div>
        <div class="stat-body"><div class="stat-value">{{ number_format($partsValue->total_skus) }}</div><div class="stat-label">Part SKUs</div></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon bg-success-soft"><i class="fa fa-boxes-stacked"></i></div>
        <div class="stat-body"><div class="stat-value">Br {{ number_format($partsValue->buying_value,0) }}</div><div class="stat-label">Parts Value</div></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon bg-warning-soft"><i class="fa fa-motorcycle"></i></div>
        <div class="stat-body"><div class="stat-value">{{ number_format($vehiclesValue->total_models) }}</div><div class="stat-label">Vehicle Models</div></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-left: 3px solid #8b5cf6"><div class="stat-icon bg-purple-soft"><i class="fa fa-warehouse"></i></div>
        <div class="stat-body"><div class="stat-value" style="color:#7c3aed">Br {{ number_format($partsValue->buying_value + $vehiclesValue->buying_value,0) }}</div><div class="stat-label">Total Stock Value</div></div></div>
    </div>
</div>

<div class="row g-3">
<div class="col-12 col-md-6">
<div class="card">
<div class="card-header d-flex align-items-center gap-2"><i class="fa fa-gears text-primary"></i><span>Spare Parts by Category</span></div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Category</th><th>Parts</th><th>Qty (Unsold)</th><th>Value</th></tr></thead>
    <tbody>
        @forelse($byCat as $cat)
        <tr>
            <td class="fw-semibold small">{{ $cat->name }}</td>
            <td class="text-muted small">{{ $cat->parts_count }}</td>
            <td class="fw-semibold text-primary">{{ number_format($cat->total_qty) }}</td>
            <td class="fw-semibold">Br {{ number_format($cat->value,2) }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="text-center text-muted py-3">No data.</td></tr>
        @endforelse
    </tbody>
    @if($byCat->count())
    <tfoot class="table-light fw-bold">
        <tr>
            <td>Total</td>
            <td>{{ $byCat->sum('parts_count') }}</td>
            <td>{{ number_format($byCat->sum('total_qty')) }}</td>
            <td>Br {{ number_format($byCat->sum('value'),2) }}</td>
        </tr>
    </tfoot>
    @endif
</table>
</div>
</div>
</div>

<div class="col-12 col-md-6">
<div class="card">
<div class="card-header d-flex align-items-center gap-2"><i class="fa fa-motorcycle text-primary"></i><span>Vehicles by Type</span></div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Type</th><th>Models</th><th>Units (Unsold)</th><th>Value</th></tr></thead>
    <tbody>
        @forelse($byType as $vt)
        <tr>
            <td class="fw-semibold small">{{ $vt->name }}</td>
            <td class="text-muted small">{{ $vt->model_count }}</td>
            <td class="fw-semibold text-primary">{{ number_format($vt->total_qty) }}</td>
            <td class="fw-semibold">Br {{ number_format($vt->value,2) }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="text-center text-muted py-3">No data.</td></tr>
        @endforelse
    </tbody>
    <tfoot class="table-light fw-bold">
        <tr>
            <td>Total</td>
            <td>{{ $byType->sum('model_count') }}</td>
            <td>{{ number_format($byType->sum('total_qty')) }}</td>
            <td>Br {{ number_format($byType->sum('value'),2) }}</td>
        </tr>
    </tfoot>
</table>
</div>
</div>
</div>
</div>
@endsection
