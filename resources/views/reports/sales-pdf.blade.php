<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; background: #fff; }

    .header { text-align: center; padding: 18px 0 10px; border-bottom: 2px solid #6366f1; margin-bottom: 14px; }
    .header h1 { font-size: 20px; color: #6366f1; font-weight: 700; letter-spacing: .5px; }
    .header .meta { font-size: 10px; color: #64748b; margin-top: 4px; }

    .summary { display: table; width: 100%; margin-bottom: 14px; border-collapse: collapse; }
    .summary-cell { display: table-cell; width: 14.2%; text-align: center; padding: 8px 4px; background: #f1f5f9; border: 1px solid #e2e8f0; }
    .summary-cell .val { font-size: 13px; font-weight: 700; color: #6366f1; }
    .summary-cell .lbl { font-size: 9px; color: #64748b; margin-top: 2px; }
    .profit-cell .val { color: {{ $totalProfit >= 0 ? '#10b981' : '#ef4444' }}; }

    table.list { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.list thead tr { background: #1e293b; color: #fff; }
    table.list thead th { padding: 7px 6px; font-size: 10px; font-weight: 600; text-align: left; }
    table.list tbody tr:nth-child(even) { background: #f8fafc; }
    table.list tbody td { padding: 6px 6px; font-size: 10px; border-bottom: 1px solid #e2e8f0; }
    table.list tfoot tr { background: #e0e7ff; font-weight: 700; }
    table.list tfoot td { padding: 7px 6px; font-size: 10px; }

    .badge { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 9px; font-weight: 600; }
    .badge-paid { background: #d1fae5; color: #065f46; }
    .badge-partial { background: #fef3c7; color: #92400e; }
    .badge-unpaid { background: #fee2e2; color: #991b1b; }

    .footer { margin-top: 18px; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }

    .type-badge { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 9px; font-weight: 600;
        background: {{ $itemType === 'vehicle' ? '#fff7ed' : ($itemType === 'spare_part' ? '#f0f9ff' : '#f1f5f9') }};
        color: {{ $itemType === 'vehicle' ? '#c2410c' : ($itemType === 'spare_part' ? '#0369a1' : '#475569') }};
    }
</style>
</head>
<body>

<div class="header">
    <h1>Sales Report</h1>
    <div class="meta">
        Period: {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} &mdash; {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <span class="type-badge">
            {{ $itemType === 'vehicle' ? 'Vehicles Only' : ($itemType === 'spare_part' ? 'Spare Parts Only' : 'All Items') }}
        </span>
        @if($warehouseName)
            &nbsp;&nbsp;|&nbsp;&nbsp; {{ $warehouseName }}
        @endif
        &nbsp;&nbsp;|&nbsp;&nbsp; Generated: {{ now()->format('M d, Y H:i') }}
    </div>
</div>

{{-- Summary --}}
<div class="summary">
    <div class="summary-cell">
        <div class="val">{{ number_format($summary->total_invoices) }}</div>
        <div class="lbl">Invoices</div>
    </div>
    <div class="summary-cell">
        <div class="val">Br {{ number_format($summary->gross_revenue, 0) }}</div>
        <div class="lbl">Revenue</div>
    </div>
    <div class="summary-cell">
        <div class="val">Br {{ number_format($summary->total_discounts, 0) }}</div>
        <div class="lbl">Discounts</div>
    </div>
    <div class="summary-cell">
        <div class="val">Br {{ number_format($summary->total_tax, 0) }}</div>
        <div class="lbl">Tax</div>
    </div>
    <div class="summary-cell">
        <div class="val">Br {{ number_format($summary->total_collected, 0) }}</div>
        <div class="lbl">Collected</div>
    </div>
    <div class="summary-cell">
        <div class="val">Br {{ number_format($summary->total_outstanding, 0) }}</div>
        <div class="lbl">Outstanding</div>
    </div>
    <div class="summary-cell profit-cell">
        <div class="val">Br {{ number_format($totalProfit, 0) }}</div>
        <div class="lbl">Net Profit</div>
    </div>
</div>

{{-- Sales table --}}
<table class="list">
    <thead>
        <tr>
            <th>#</th>
            <th>Invoice</th>
            <th>Customer</th>
            <th>Date</th>
            <th>Total (Br)</th>
            <th>Paid (Br)</th>
            <th>Balance (Br)</th>
            <th>Payment</th>
            <th>Warehouse</th>
        </tr>
    </thead>
    <tbody>
        @forelse($sales as $i => $s)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td><strong>{{ $s->invoice_number }}</strong></td>
            <td>{{ $s->customer_name ?? 'Walk-in' }}</td>
            <td>{{ $s->sale_date->format('M d, Y') }}</td>
            <td>{{ number_format($s->total, 2) }}</td>
            <td>{{ number_format($s->paid_amount, 2) }}</td>
            <td>{{ $s->balance > 0 ? number_format($s->balance, 2) : '—' }}</td>
            <td>
                <span class="badge badge-{{ $s->payment_status_badge ?? 'secondary' }}">
                    {{ ucfirst($s->payment_status ?? '') }}
                </span>
            </td>
            <td>{{ $s->warehouse?->name ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center;padding:20px;color:#94a3b8">No sales in this period.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4"><strong>TOTAL ({{ $sales->count() }} records)</strong></td>
            <td><strong>Br {{ number_format($summary->gross_revenue, 2) }}</strong></td>
            <td><strong>Br {{ number_format($summary->total_collected, 2) }}</strong></td>
            <td><strong>Br {{ number_format($summary->total_outstanding, 2) }}</strong></td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

<div class="footer">
    &copy; {{ date('Y') }} Abush Spare Part &mdash; Sales Report &mdash; {{ now()->format('M d, Y H:i') }}
</div>

</body>
</html>
