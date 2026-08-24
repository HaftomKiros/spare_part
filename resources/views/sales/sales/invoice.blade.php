<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $sale->invoice_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;font-size:13px;color:#111;background:#fff;padding:30px}
        .invoice-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:30px;padding-bottom:20px;border-bottom:2px solid #4f46e5}
        .company-name{font-size:22px;font-weight:700;color:#4f46e5}
        .company-info{font-size:11px;color:#6b7280;margin-top:4px;line-height:1.7}
        .invoice-title{text-align:right}
        .invoice-title h2{font-size:26px;font-weight:700;color:#4f46e5;letter-spacing:.05em}
        .invoice-title p{color:#6b7280;font-size:11px;margin-top:4px}
        .bill-section{display:flex;justify-content:space-between;margin-bottom:24px}
        .bill-to h4,.bill-details h4{font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;letter-spacing:.06em;margin-bottom:8px}
        .bill-to p,.bill-details p{font-size:12px;line-height:1.8}
        .bill-details{text-align:right}
        table{width:100%;border-collapse:collapse;margin-bottom:20px}
        thead th{background:#4f46e5;color:#fff;padding:10px 12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
        tbody td{padding:10px 12px;border-bottom:1px solid #f3f4f6;font-size:12px}
        tbody tr:last-child td{border-bottom:none}
        tbody tr:nth-child(even){background:#fafbff}
        .totals-table{width:280px;margin-left:auto}
        .totals-table td{padding:5px 12px;font-size:12px}
        .totals-table .grand-total{font-size:15px;font-weight:700;color:#4f46e5;background:#eef2ff}
        .notes{background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px;margin-top:16px;font-size:12px;color:#6b7280}
        .footer{text-align:center;margin-top:30px;padding-top:20px;border-top:1px solid #e5e7eb;font-size:11px;color:#9ca3af}
        .badge-paid{display:inline-block;background:#dcfce7;color:#15803d;padding:2px 10px;border-radius:20px;font-weight:600;font-size:11px}
        @media print{
            body{padding:0}
            .no-print{display:none}
        }
    </style>
</head>
<body>

<!-- Print Button -->
<div class="no-print" style="margin-bottom:20px">
    <button onclick="window.print()" style="background:#4f46e5;color:#fff;border:none;padding:8px 20px;border-radius:8px;cursor:pointer;font-size:13px">
        🖨 Print Invoice
    </button>
    <a href="{{ route('sales.show',$sale) }}" style="margin-left:10px;color:#6b7280;font-size:13px">← Back</a>
</div>

<!-- Header -->
<div class="invoice-header">
    <div>
        <div class="company-name">{{ $company->company_name }}</div>
        <div class="company-info">
            {{ $company->company_address }}<br>
            📞 {{ $company->company_phone }}<br>
            {{ $company->company_email }}
            @if($company->tax_number)<br>TIN: {{ $company->tax_number }}@endif
        </div>
    </div>
    <div class="invoice-title">
        <h2>INVOICE</h2>
        <p>{{ $sale->invoice_number }}</p>
    </div>
</div>

<!-- Bill To & Details -->
<div class="bill-section">
    <div class="bill-to">
        <h4>Bill To</h4>
        <p>
            <strong>{{ $sale->customer_name }}</strong><br>
            @if($sale->customer)
                {{ $sale->customer->phone }}<br>
                @if($sale->customer->city){{ $sale->customer->city }}@endif
            @endif
        </p>
    </div>
    <div class="bill-details">
        <h4>Invoice Details</h4>
        <p>
            Date: <strong>{{ $sale->sale_date->format('M d, Y') }}</strong><br>
            Payment: <strong>{{ ucfirst(str_replace('_',' ',$sale->payment_method)) }}</strong><br>
            Status: <span class="badge-paid">{{ ucfirst($sale->payment_status) }}</span><br>
            Served by: {{ $sale->user->name }}
        </p>
    </div>
</div>

<!-- Items Table -->
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Description</th>
            <th>Unit Price</th>
            <th>Qty</th>
            <th>Discount</th>
            <th style="text-align:right">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sale->items as $i => $item)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>
                <strong>{{ $item->item_name }}</strong>
                @if($item->item_type === 'spare_part' && $item->sparePart)
                    <br><span style="color:#9ca3af;font-size:11px">Part #: {{ $item->sparePart->part_number }}</span>
                @endif
            </td>
            <td>{{ $company->currency_symbol }} {{ number_format($item->unit_price,2) }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ $item->discount > 0 ? $company->currency_symbol.' '.number_format($item->discount,2) : '—' }}</td>
            <td style="text-align:right;font-weight:600">{{ $company->currency_symbol }} {{ number_format($item->total,2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- Totals -->
<table class="totals-table">
    <tr>
        <td class="text-muted">Subtotal</td>
        <td style="text-align:right">{{ $company->currency_symbol }} {{ number_format($sale->subtotal,2) }}</td>
    </tr>
    @if($sale->discount > 0)
    <tr>
        <td class="text-muted">Discount</td>
        <td style="text-align:right;color:#dc2626">-{{ $company->currency_symbol }} {{ number_format($sale->discount,2) }}</td>
    </tr>
    @endif
    @if($sale->tax > 0)
    <tr>
        <td class="text-muted">Tax</td>
        <td style="text-align:right">+{{ $company->currency_symbol }} {{ number_format($sale->tax,2) }}</td>
    </tr>
    @endif
    <tr class="grand-total">
        <td><strong>TOTAL</strong></td>
        <td style="text-align:right"><strong>{{ $company->currency_symbol }} {{ number_format($sale->total,2) }}</strong></td>
    </tr>
    <tr>
        <td class="text-muted">Paid</td>
        <td style="text-align:right;color:#16a34a">{{ $company->currency_symbol }} {{ number_format($sale->paid_amount,2) }}</td>
    </tr>
    @if($sale->balance > 0)
    <tr>
        <td class="text-muted">Balance Due</td>
        <td style="text-align:right;color:#dc2626;font-weight:700">{{ $company->currency_symbol }} {{ number_format($sale->balance,2) }}</td>
    </tr>
    @endif
</table>

@if($sale->notes)
<div class="notes"><strong>Notes:</strong> {{ $sale->notes }}</div>
@endif

<div class="footer">
    Thank you for your business! — {{ $company->company_name }}
    @if($company->website)<br>{{ $company->website }}@endif
</div>
</body>
</html>
