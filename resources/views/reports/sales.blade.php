@extends('layouts.app')
@section('title','Sales Report')
@section('breadcrumb')
    <li class="breadcrumb-item active">Reports</li>
    <li class="breadcrumb-item active">Sales</li>
@endsection
@section('content')

@include('partials.report-nav', ['active' => 'sales'])

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0" style="color:#1e293b">Sales Report</h5>
        <div class="text-muted small">Revenue analysis for the selected period</div>
    </div>
    @if($warehouseId)
    <span class="rpt-period-badge"><i class="fa fa-warehouse"></i>{{ $warehouses->find($warehouseId)?->name }}</span>
    @endif
</div>

<div class="rpt-filter-card d-flex flex-wrap gap-3 align-items-end">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end w-100">
        <div>
            <label class="form-label">From</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
        </div>
        <div>
            <label class="form-label">To</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
        </div>
        <div>
            <label class="form-label">Item Type</label>
            <select name="item_type" class="form-select form-select-sm">
                <option value="">All Types</option>
                <option value="spare_part" {{ $itemType === 'spare_part' ? 'selected' : '' }}>Spare Parts</option>
                <option value="vehicle"    {{ $itemType === 'vehicle'    ? 'selected' : '' }}>Vehicles</option>
            </select>
        </div>
        @include('partials.warehouse-filter')
        <div class="mt-auto">
            <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-filter me-1"></i>Apply</button>
        </div>
        <div class="mt-auto ms-auto d-flex align-items-center gap-2">
            <span class="rpt-period-badge">
                <i class="fa fa-calendar-days"></i>
                {{ \Carbon\Carbon::parse($dateFrom)->format('M d') }} — {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
            </span>
            {{-- Export buttons --}}
            <a href="{{ route('reports.sales.export.excel', array_filter(['date_from'=>$dateFrom,'date_to'=>$dateTo,'warehouse_id'=>$warehouseId,'item_type'=>$itemType])) }}"
               class="btn btn-sm btn-success" title="Export Excel">
                <i class="fa fa-file-excel me-1"></i>Excel
            </a>
            <a href="{{ route('reports.sales.export.pdf', array_filter(['date_from'=>$dateFrom,'date_to'=>$dateTo,'warehouse_id'=>$warehouseId,'item_type'=>$itemType])) }}"
               class="btn btn-sm btn-danger" title="Export PDF" target="_blank">
                <i class="fa fa-file-pdf me-1"></i>PDF
            </a>
        </div>
    </form>
</div>

<div class="row g-3 mb-4">

    {{-- ── Invoices card — acts as the "All" toggle ── --}}
    <div class="col-6 col-md-2">
        <div class="stat-card pmt-toggle active" data-method="all" role="button" style="cursor:pointer;border:2px solid transparent;transition:border-color .2s">
            <div class="stat-icon bg-primary-soft"><i class="fa fa-receipt"></i></div>
            <div class="stat-body">
                <div class="stat-value pmt-val" data-all="{{ $summary->total_invoices }}">{{ number_format($summary->total_invoices) }}</div>
                <div class="stat-label pmt-lbl" data-all="Invoices">Invoices</div>
                {{-- per-method counts hidden --}}
                @foreach($paymentMethods as $key => $pm)
                    <span class="d-none pmt-count-{{ $key }}">{{ $paymentBreakdown[$key]->count ?? 0 }}</span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Revenue ── --}}
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-icon bg-success-soft"><i class="fa fa-chart-line"></i></div>
            <div class="stat-body">
                <div class="stat-value" id="rev-value">Br {{ number_format($summary->gross_revenue,0) }}</div>
                <div class="stat-label">Revenue</div>
            </div>
        </div>
    </div>

    {{-- ── Discounts ── --}}
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-icon bg-warning-soft"><i class="fa fa-tag"></i></div>
            <div class="stat-body"><div class="stat-value">Br {{ number_format($summary->total_discounts,0) }}</div><div class="stat-label">Discounts</div></div>
        </div>
    </div>

    {{-- ── Tax ── --}}
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-icon bg-info-soft"><i class="fa fa-percent"></i></div>
            <div class="stat-body"><div class="stat-value">Br {{ number_format($summary->total_tax,0) }}</div><div class="stat-label">Tax</div></div>
        </div>
    </div>

    {{-- ── Collected + Outstanding combined toggle card ── --}}
    <div class="col-6 col-md-2">
        <div class="stat-card" style="position:relative;min-height:90px">
            {{-- Collected face --}}
            <div id="face-collected" style="transition:opacity .25s">
                <div class="stat-icon bg-success-soft"><i class="fa fa-circle-check"></i></div>
                <div class="stat-body">
                    <div class="stat-value" id="col-value">Br {{ number_format($summary->total_collected,0) }}</div>
                    <div class="stat-label d-flex align-items-center gap-1">
                        Collected
                        <button onclick="toggleColOut()" class="btn btn-link btn-sm p-0 ms-1 text-muted" title="Show Outstanding" style="font-size:.7rem;line-height:1">
                            <i class="fa fa-arrow-right-arrow-left"></i>
                        </button>
                    </div>
                </div>
            </div>
            {{-- Outstanding face --}}
            <div id="face-outstanding" style="display:none">
                <div class="stat-icon bg-danger-soft"><i class="fa fa-clock"></i></div>
                <div class="stat-body">
                    <div class="stat-value" id="out-value">Br {{ number_format($summary->total_outstanding,0) }}</div>
                    <div class="stat-label d-flex align-items-center gap-1">
                        Outstanding
                        <button onclick="toggleColOut()" class="btn btn-link btn-sm p-0 ms-1 text-muted" title="Show Collected" style="font-size:.7rem;line-height:1">
                            <i class="fa fa-arrow-right-arrow-left"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Net Sales Profit ── --}}
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-icon bg-success-soft"><i class="fa fa-sack-dollar"></i></div>
            <div class="stat-body">
                <div class="stat-value {{ $totalProfit < 0 ? 'text-danger' : 'text-success' }}">Br {{ number_format($totalProfit,0) }}</div>
                <div class="stat-label">Net Sales Profit</div>
            </div>
        </div>
    </div>

</div>

{{-- ── Payment method breakdown row ── --}}
<div class="row g-3 mb-4">
    @foreach($paymentMethods as $key => $pm)
    @php $row = $paymentBreakdown[$key] ?? null; @endphp
    <div class="col-6 col-md-3">
        <div class="stat-card pmt-toggle {{ $loop->first ? '' : '' }}"
             data-method="{{ $key }}"
             data-revenue="{{ $row?->revenue ?? 0 }}"
             data-collected="{{ $row?->collected ?? 0 }}"
             data-outstanding="{{ $row?->outstanding ?? 0 }}"
             data-count="{{ $row?->count ?? 0 }}"
             role="button" style="cursor:pointer;border:2px solid transparent;transition:border-color .2s">
            <div class="stat-icon bg-{{ $pm['color'] }}-soft"><i class="fa {{ $pm['icon'] }}"></i></div>
            <div class="stat-body">
                <div class="stat-value">Br {{ number_format($row?->revenue ?? 0, 0) }}</div>
                <div class="stat-label">{{ $pm['label'] }} <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:.65rem">{{ $row?->count ?? 0 }}</span></div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card mb-4">
<div class="card-header d-flex align-items-center gap-2">
    <i class="fa fa-chart-bar text-primary"></i><span>Daily Sales</span>
    <span class="badge bg-primary-subtle text-primary-emphasis ms-auto" style="font-size:.72rem">{{ $daily->count() }} days</span>
</div>
<div class="card-body"><div class="chart-container"><canvas id="dailyChart"></canvas></div></div>
</div>

<div class="card">
<div class="card-header d-flex align-items-center gap-2 flex-wrap">
    <i class="fa fa-list text-primary"></i><span>Sales List</span>
    <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:.72rem">{{ $sales->total() }} records</span>
    {{-- Item type toggle tabs --}}
    <div class="ms-auto d-flex gap-1">
        <a href="{{ request()->fullUrlWithQuery(['item_type' => '']) }}"
           class="btn btn-sm {{ !$itemType ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="fa fa-layer-group me-1"></i>All
        </a>
        <a href="{{ request()->fullUrlWithQuery(['item_type' => 'vehicle']) }}"
           class="btn btn-sm {{ $itemType === 'vehicle' ? 'btn-warning' : 'btn-outline-secondary' }}">
            <i class="fa fa-motorcycle me-1"></i>Vehicles
        </a>
        <a href="{{ request()->fullUrlWithQuery(['item_type' => 'spare_part']) }}"
           class="btn btn-sm {{ $itemType === 'spare_part' ? 'btn-info' : 'btn-outline-secondary' }}">
            <i class="fa fa-gears me-1"></i>Spare Parts
        </a>
    </div>
</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th>Total</th><th>Paid</th><th>Balance</th><th>Payment</th><th class="d-none d-lg-table-cell">Warehouse</th><th>By</th></tr></thead>
    <tbody>
        @forelse($sales as $s)
        <tr>
            <td><a href="{{ route('sales.show',$s) }}" class="text-primary fw-semibold">{{ $s->invoice_number }}</a></td>
            <td class="text-muted small">{{ $s->customer_name ?? 'Walk-in' }}</td>
            <td class="text-muted small">{{ $s->sale_date->format('M d, Y') }}</td>
            <td class="fw-semibold">Br {{ number_format($s->total,2) }}</td>
            <td class="text-success small">Br {{ number_format($s->paid_amount,2) }}</td>
            <td class="{{ $s->balance > 0 ? 'text-danger fw-semibold' : 'text-muted' }} small">{{ $s->balance > 0 ? 'Br '.number_format($s->balance,2) : '—' }}</td>
            <td><span class="badge bg-{{ $s->payment_status_badge }}">{{ ucfirst($s->payment_status) }}</span></td>
            <td class="small text-muted d-none d-lg-table-cell">{{ $s->warehouse?->name ?? '—' }}</td>
            <td class="small text-muted">{{ $s->user->name }}</td>
        </tr>
        @empty
        <tr><td colspan="9" class="text-center text-muted py-5">
            <i class="fa fa-chart-line fs-2 d-block mb-2 opacity-25"></i>No sales in this period.
        </td></tr>
        @endforelse
    </tbody>
</table>
</div>
@if($sales->hasPages())<div class="card-body border-top py-3">{{ $sales->links() }}</div>@endif
</div>
@endsection
@push('scripts')
<script>
// ── Collected / Outstanding toggle ───────────────────────────────────
let showingCollected = true;
function toggleColOut() {
    showingCollected = !showingCollected;
    document.getElementById('face-collected').style.display  = showingCollected ? '' : 'none';
    document.getElementById('face-outstanding').style.display = showingCollected ? 'none' : '';
}

// ── Payment method filter toggle ─────────────────────────────────────
// Data passed from PHP
const allRevenue     = {{ (float)($summary->gross_revenue ?? 0) }};
const allCollected   = {{ (float)($summary->total_collected ?? 0) }};
const allOutstanding = {{ (float)($summary->total_outstanding ?? 0) }};
const allInvoices    = {{ (int)($summary->total_invoices ?? 0) }};

// Daily data keyed by date for all methods
const dailyLabels      = @json($daily->pluck('date')->map(fn($d)=>\Carbon\Carbon::parse($d)->format('M d')));
const dailyRevAll      = @json($daily->pluck('total')->map(fn($v)=>(float)$v));
const dailyProfit      = @json($daily->pluck('profit')->map(fn($v)=>(float)$v));
const dailyVehicleRev  = @json($daily->pluck('vehicle_revenue')->map(fn($v)=>(float)$v));
const dailySpareRev    = @json($daily->pluck('spare_part_revenue')->map(fn($v)=>(float)$v));

// Per-method daily revenue: { cash: [...], bank_transfer: [...], ... }
const methodLabels = @json(array_keys($paymentMethods));
const dailyDates   = @json($daily->pluck('date'));

const dailyByMethod = @json($dailyByMethod->map(fn($rows)=>$rows->keyBy('payment_method')));

function buildMethodDaily(method) {
    return dailyDates.map(date => {
        const byDate = dailyByMethod[date];
        if (!byDate) return 0;
        const row = byDate[method];
        return row ? parseFloat(row.revenue) : 0;
    });
}

// ── Chart setup ──────────────────────────────────────────────────────
const ctx = document.getElementById('dailyChart');
let chart;
if (ctx) {
    chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dailyLabels,
            datasets: [
                {
                    label: 'Revenue (Br)',
                    data: dailyRevAll,
                    backgroundColor: 'rgba(99,102,241,.75)',
                    borderRadius: 6,
                    borderSkipped: false,
                    order: 2
                },
                {
                    label: 'Vehicles (Br)',
                    data: dailyVehicleRev,
                    backgroundColor: 'rgba(249,115,22,.65)',
                    borderRadius: 4,
                    borderSkipped: false,
                    order: 3
                },
                {
                    label: 'Spare Parts (Br)',
                    data: dailySpareRev,
                    backgroundColor: 'rgba(14,165,233,.65)',
                    borderRadius: 4,
                    borderSkipped: false,
                    order: 4
                },
                {
                    label: 'Net Profit (Br)',
                    data: dailyProfit,
                    type: 'line',
                    borderColor: 'rgba(16,185,129,1)',
                    backgroundColor: 'rgba(16,185,129,.15)',
                    pointBackgroundColor: 'rgba(16,185,129,1)',
                    pointRadius: 4,
                    fill: true,
                    tension: 0.3,
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: 'top' },
                tooltip: { callbacks: { label: c => 'Br ' + parseFloat(c.raw).toLocaleString('en-US', { minimumFractionDigits: 2 }) } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => 'Br ' + v.toLocaleString() }, grid: { color: 'rgba(0,0,0,.04)' } },
                x: { grid: { display: false } }
            }
        }
    });
}

// ── Toggle click handler ──────────────────────────────────────────────
document.querySelectorAll('.pmt-toggle').forEach(card => {
    card.addEventListener('click', function () {
        const method = this.dataset.method;

        // Highlight active card
        document.querySelectorAll('.pmt-toggle').forEach(c => c.style.borderColor = 'transparent');
        this.style.borderColor = 'var(--bs-primary, #6366f1)';

        if (method === 'all') {
            // Restore all totals
            document.getElementById('rev-value').textContent  = 'Br ' + allRevenue.toLocaleString('en-US', {maximumFractionDigits:0});
            document.getElementById('col-value').textContent  = 'Br ' + allCollected.toLocaleString('en-US', {maximumFractionDigits:0});
            document.getElementById('out-value').textContent  = 'Br ' + allOutstanding.toLocaleString('en-US', {maximumFractionDigits:0});
            document.querySelector('.pmt-val').textContent    = allInvoices.toLocaleString();
            document.querySelector('.pmt-lbl').textContent    = 'Invoices';
            // Restore chart
            if (chart) {
                chart.data.datasets[0].data   = dailyRevAll;
                chart.data.datasets[0].label  = 'Revenue (Br)';
                chart.data.datasets[0].hidden = false;
                chart.data.datasets[1].hidden = false; // vehicles
                chart.data.datasets[2].hidden = false; // spare parts
                chart.data.datasets[3].hidden = false; // profit line
                chart.update();
            }
        } else {
            const rev  = parseFloat(this.dataset.revenue  || 0);
            const col  = parseFloat(this.dataset.collected || 0);
            const out  = parseFloat(this.dataset.outstanding || 0);
            const cnt  = parseInt(this.dataset.count || 0);
            const lbl  = this.querySelector('.stat-label')?.textContent?.replace(/\d+/g,'').trim() ?? method;

            document.getElementById('rev-value').textContent = 'Br ' + rev.toLocaleString('en-US', {maximumFractionDigits:0});
            document.getElementById('col-value').textContent = 'Br ' + col.toLocaleString('en-US', {maximumFractionDigits:0});
            document.getElementById('out-value').textContent = 'Br ' + out.toLocaleString('en-US', {maximumFractionDigits:0});
            document.querySelector('.pmt-val').textContent   = cnt.toLocaleString();
            document.querySelector('.pmt-lbl').textContent   = lbl + ' Invoices';

            // Update chart to show only this method's revenue; hide type breakdown and profit line
            if (chart) {
                chart.data.datasets[0].data   = buildMethodDaily(method);
                chart.data.datasets[0].label  = lbl + ' Revenue (Br)';
                chart.data.datasets[1].hidden = true; // hide vehicles
                chart.data.datasets[2].hidden = true; // hide spare parts
                chart.data.datasets[3].hidden = true; // hide profit line
                chart.update();
            }
        }
    });
});
</script>
@endpush
