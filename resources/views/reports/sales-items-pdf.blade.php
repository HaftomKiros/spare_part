<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size:10px; color:#1e293b; }
    .header { text-align:center; padding:14px 0 8px; border-bottom:2px solid #6366f1; margin-bottom:12px; }
    .header h1 { font-size:18px; color:#6366f1; font-weight:700; }
    .header .meta { font-size:9px; color:#64748b; margin-top:3px; }
    table { width:100%; border-collapse:collapse; }
    thead tr { background:#1e293b; color:#fff; }
    thead th { padding:6px 7px; font-size:9px; font-weight:600; text-align:left; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:5px 7px; border-bottom:1px solid #e2e8f0; font-size:9px; }
    .inv-group td { background:#eef2ff; font-weight:700; font-size:9px; color:#3730a3; }
    tfoot tr { background:#e0e7ff; font-weight:700; }
    tfoot td { padding:6px 7px; font-size:9px; }
    .type-v { background:#fff7ed; color:#c2410c; padding:1px 5px; border-radius:8px; font-size:8px; font-weight:700; }
    .type-s { background:#f0f9ff; color:#0369a1; padding:1px 5px; border-radius:8px; font-size:8px; font-weight:700; }
    .footer { margin-top:14px; text-align:center; font-size:8px; color:#94a3b8; border-top:1px solid #e2e8f0; padding-top:6px; }
</style>
</head>
<body>

<div class="header">
    <h1>Sales Items Report</h1>
    <div class="meta">
        Period: {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} &mdash; {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
        @if($itemType) &nbsp;|&nbsp; {{ $itemType === 'vehicle' ? 'Vehicles Only' : 'Spare Parts Only' }} @endif
        @if($warehouseName) &nbsp;|&nbsp; {{ $warehouseName }} @endif
        &nbsp;|&nbsp; Generated: {{ now()->format('M d, Y H:i') }}
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Invoice</th>
            <th>Customer</th>
            <th>Date</th>
            <th>Type</th>
            <th>Item Name</th>
            <th>Qty</th>
            <th>Unit Price (Br)</th>
            <th>Total (Br)</th>
            <th>Payment</th>
            <th>Warehouse</th>
        </tr>
    </thead>
    <tbody>
        @php $rowNum = 0; $grandTotal = 0; @endphp
        @foreach($sales as $sale)
            @foreach($sale->items as $item)
                @if(!$itemType || $item->item_type === $itemType)
                @php $rowNum++; $grandTotal += $item->total; @endphp
                <tr>
                    <td>{{ $rowNum }}</td>
                    <td><strong>{{ $sale->invoice_number }}</strong></td>
                    <td>{{ $sale->customer_name ?? 'Walk-in' }}</td>
                    <td>{{ $sale->sale_date->format('M d, Y') }}</td>
                    <td>
                        @if($item->item_type === 'vehicle')
                            <span class="type-v">Vehicle</span>
                        @else
                            <span class="type-s">Spare Part</span>
                        @endif
                    </td>
                    <td>{{ $item->item_name }}</td>
                    <td>{{ number_format($item->quantity) }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td><strong>{{ number_format($item->total, 2) }}</strong></td>
                    <td>{{ ucfirst(str_replace('_',' ',$sale->payment_method ?? '')) }}</td>
                    <td>{{ $sale->warehouse?->name ?? '—' }}</td>
                </tr>
                @endif
            @endforeach
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="8" style="text-align:right"><strong>Grand Total ({{ $rowNum }} items)</strong></td>
            <td><strong>Br {{ number_format($grandTotal, 2) }}</strong></td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

<div class="footer">
    &copy; {{ date('Y') }} Abush Spare Part &mdash; Sales Items Report &mdash; {{ now()->format('M d, Y H:i') }}
</div>
</body>
</html>
