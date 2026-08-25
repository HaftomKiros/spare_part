@extends('layouts.app')
@section('title','Spare Parts Report')
@section('breadcrumb')
    <li class="breadcrumb-item active">Reports</li>
    <li class="breadcrumb-item active">Spare Parts</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'Spare Parts Sales Report','subtitle'=>'Parts sold in the selected period'])

<div class="card mb-4">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-auto"><label class="form-label small mb-1">From</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
    </div>
    <div class="col-auto"><label class="form-label small mb-1">To</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
    </div>
    @include('partials.warehouse-filter')
    <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary mt-3"><i class="fa fa-filter me-1"></i>Apply</button></div>
    @if($warehouseId)
    <div class="col-auto ms-auto mt-3">
        <span class="badge bg-primary-subtle text-primary-emphasis"><i class="fa fa-warehouse me-1"></i>{{ $warehouses->find($warehouseId)?->name }}</span>
    </div>
    @endif
</form>
</div>
</div>

<div class="row g-3 mb-4">
    <div class="col-4"><div class="stat-card"><div class="stat-icon bg-success-soft"><i class="fa fa-gears"></i></div><div class="stat-body"><div class="stat-value">{{ number_format($totalQty) }}</div><div class="stat-label">Units Sold</div></div></div></div>
    <div class="col-4"><div class="stat-card"><div class="stat-icon bg-primary-soft"><i class="fa fa-chart-line"></i></div><div class="stat-body"><div class="stat-value">Br {{ number_format($totalRevenue,0) }}</div><div class="stat-label">Revenue</div></div></div></div>
    <div class="col-4"><div class="stat-card"><div class="stat-icon bg-purple-soft"><i class="fa fa-sack-dollar"></i></div><div class="stat-body"><div class="stat-value">Br {{ number_format($totalProfit,0) }}</div><div class="stat-label">Gross Profit</div></div></div></div>
</div>

<div class="card">
<div class="card-header"><i class="fa fa-gears me-2 text-primary"></i>Sales by Spare Part</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Part</th><th>Category</th><th>Unit</th><th>Qty Sold</th><th>Revenue</th><th>Cost</th><th>Profit</th><th>Margin</th></tr></thead>
    <tbody>
        @forelse($parts as $p)
        <tr>
            <td>
                <div class="fw-semibold small">{{ $p->name }}</div>
                <div class="text-muted" style="font-size:.72rem">{{ $p->part_number }}</div>
            </td>
            <td><span class="badge" style="background:#f1f5f9;color:#475569;font-size:.72rem;padding:3px 8px;border-radius:5px">{{ $p->category_name }}</span></td>
            <td class="text-muted small">{{ $p->unit }}</td>
            <td class="fw-semibold text-primary">{{ $p->qty_sold }}</td>
            <td>Br {{ number_format($p->revenue,2) }}</td>
            <td class="text-muted">Br {{ number_format($p->cost,2) }}</td>
            <td class="{{ $p->profit >= 0 ? 'text-success' : 'text-danger' }} fw-semibold">Br {{ number_format($p->profit,2) }}</td>
            <td>
                @php $m = $p->revenue > 0 ? round(($p->profit/$p->revenue)*100,1) : 0 @endphp
                <span class="{{ $m >= 0 ? 'text-success' : 'text-danger' }}">{{ $m }}%</span>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center text-muted py-4">No spare part sales in this period.</td></tr>
        @endforelse
    </tbody>
    @if($parts->count())
    <tfoot class="table-light fw-bold">
        <tr>
            <td colspan="3">Total</td>
            <td>{{ $totalQty }}</td>
            <td>Br {{ number_format($totalRevenue,2) }}</td>
            <td>Br {{ number_format($parts->sum('cost'),2) }}</td>
            <td class="{{ $totalProfit >= 0 ? 'text-success' : 'text-danger' }}">Br {{ number_format($totalProfit,2) }}</td>
            <td>{{ $totalRevenue > 0 ? round(($totalProfit/$totalRevenue)*100,1) : 0 }}%</td>
        </tr>
    </tfoot>
    @endif
</table>
</div>
</div>
@endsection
