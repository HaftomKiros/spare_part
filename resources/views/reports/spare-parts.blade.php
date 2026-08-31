@extends('layouts.app')
@section('title','Spare Parts Report')
@section('breadcrumb')
    <li class="breadcrumb-item active">Reports</li>
    <li class="breadcrumb-item active">Spare Parts</li>
@endsection
@section('content')

@include('partials.report-nav', ['active' => 'spare-parts'])

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0" style="color:#1e293b">Spare Parts Sales Report</h5>
        <div class="text-muted small">Parts sold in the selected period</div>
    </div>
</div>

<div class="rpt-filter-card">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
        <div><label class="form-label">From</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
        </div>
        <div><label class="form-label">To</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
        </div>
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
    <div class="col-4"><div class="stat-card"><div class="stat-icon bg-success-soft"><i class="fa fa-gears"></i></div><div class="stat-body"><div class="stat-value">{{ number_format($totalQty) }}</div><div class="stat-label">Units Sold</div></div></div></div>
    <div class="col-4"><div class="stat-card"><div class="stat-icon bg-primary-soft"><i class="fa fa-chart-line"></i></div><div class="stat-body"><div class="stat-value">Br {{ number_format($totalRevenue,0) }}</div><div class="stat-label">Revenue</div></div></div></div>
    <div class="col-4"><div class="stat-card"><div class="stat-icon bg-purple-soft"><i class="fa fa-sack-dollar"></i></div><div class="stat-body"><div class="stat-value {{ $totalProfit >= 0 ? 'text-success' : 'text-danger' }}">Br {{ number_format($totalProfit,0) }}</div><div class="stat-label">Gross Profit</div></div></div></div>
</div>

<div class="card">
<div class="card-header d-flex align-items-center gap-2">
    <i class="fa fa-gears text-primary"></i><span>Sales by Spare Part</span>
    <span class="badge bg-secondary-subtle text-secondary ms-auto" style="font-size:.72rem">{{ $parts->count() }} parts</span>
</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Part</th><th>Vehicle Models</th><th>Unit</th><th>Qty Sold</th><th>Revenue</th><th>Cost</th><th>Profit</th><th>Margin</th></tr></thead>
    <tbody>
        @forelse($parts as $p)
        @php $margin = $p->revenue > 0 ? round(($p->profit/$p->revenue)*100,1) : 0; @endphp
        <tr>
            <td>
                <div class="fw-semibold small">{{ $p->name }}</div>
                <div class="text-muted" style="font-size:.72rem">{{ $p->part_number }}</div>
            </td>
            <td style="max-width:180px">
                @php
                    $vmList = $p->vehicles && $p->vehicles !== '—' ? explode(', ', $p->vehicles) : [];
                    $vmCount = count($vmList);
                @endphp
                @if($vmCount === 0)
                    <span class="text-muted">—</span>
                @else
                    <span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:.72rem;padding:3px 8px;border-radius:5px">
                        {{ $vmList[0] }}@if($vmCount > 1) &hellip;@endif
                    </span>
                    @if($vmCount > 1)
                        <span style="font-size:.68rem;color:#6366f1;cursor:pointer;text-decoration:underline"
                              onclick="openSpVehicleModal('{{ addslashes($p->name) }}', '{{ addslashes($p->vehicles) }}')">
                            +{{ $vmCount - 1 }} more
                        </span>
                    @endif
                @endif
            </td>
            <td class="text-muted small">{{ $p->unit }}</td>
            <td class="fw-semibold text-primary">{{ number_format($p->qty_sold) }}</td>
            <td class="fw-semibold">Br {{ number_format($p->revenue,2) }}</td>
            <td class="text-muted small">Br {{ number_format($p->cost,2) }}</td>
            <td class="{{ $p->profit >= 0 ? 'text-success' : 'text-danger' }} fw-semibold">Br {{ number_format($p->profit,2) }}</td>
            <td>
                <div class="d-flex align-items-center gap-1">
                    <div class="progress flex-grow-1" style="height:4px;min-width:40px">
                        <div class="progress-bar bg-{{ $margin >= 0 ? 'success' : 'danger' }}" style="width:{{ min(100,abs($margin)) }}%"></div>
                    </div>
                    <span class="small {{ $margin >= 0 ? 'text-success' : 'text-danger' }}">{{ $margin }}%</span>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center text-muted py-5">
            <i class="fa fa-gears fs-2 d-block mb-2 opacity-25"></i>No spare part sales in this period.
        </td></tr>
        @endforelse
    </tbody>
    @if($parts->count())
    <tfoot class="table-light fw-bold">
        <tr>
            <td colspan="3">Total</td>
            <td>{{ number_format($totalQty) }}</td>
            <td>Br {{ number_format($totalRevenue,2) }}</td>
            <td class="text-muted">Br {{ number_format($parts->sum('cost'),2) }}</td>
            <td class="{{ $totalProfit >= 0 ? 'text-success' : 'text-danger' }}">Br {{ number_format($totalProfit,2) }}</td>
            <td class="{{ $totalProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ $totalRevenue > 0 ? round(($totalProfit/$totalRevenue)*100,1) : 0 }}%</td>
        </tr>
    </tfoot>
    @endif
</table>
</div>
</div>

{{-- Vehicle Models Modal --}}
<div class="modal fade" id="spVehicleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="fa fa-motorcycle me-2 text-primary"></i><span id="spVehModalPartName"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Compatible vehicle models:</p>
                <div id="spVehModalList" class="d-flex flex-wrap gap-2"></div>
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
function openSpVehicleModal(partName, vehicles) {
    document.getElementById('spVehModalPartName').textContent = partName;
    const list = document.getElementById('spVehModalList');
    list.innerHTML = '';
    vehicles.split(', ').forEach(function(v) {
        if (!v.trim()) return;
        const badge = document.createElement('span');
        badge.className = 'badge';
        badge.style = 'background:#e0e7ff;color:#3730a3;font-size:.8rem;padding:5px 10px;border-radius:6px';
        badge.textContent = v.trim();
        list.appendChild(badge);
    });
    new bootstrap.Modal(document.getElementById('spVehicleModal')).show();
}
</script>
@endpush