@extends('layouts.app')
@section('title', 'Sale '.$sale->invoice_number)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
    <li class="breadcrumb-item active">{{ $sale->invoice_number }}</li>
@endsection
@section('content')
@include('partials.page-header',[
    'title'   => 'Sale: '.$sale->invoice_number,
    'subtitle'=> $sale->sale_date->format('M d, Y'),
    'actions' => [
        ['label'=>'Print Invoice','url'=>route('sales.invoice',$sale),'icon'=>'fa-print','class'=>'btn-outline-primary'],
        ['label'=>'Edit Sale','url'=>route('sales.edit',$sale),'icon'=>'fa-pen','class'=>'btn-outline-secondary'],
        ['label'=>'New Return','url'=>route('sales.returns.create',['sale_id'=>$sale->id]),'icon'=>'fa-rotate-left','class'=>'btn-outline-warning'],
    ],
])

{{-- Delete form (triggered by button below) --}}
<form id="deleteSaleForm" method="POST" action="{{ route('sales.destroy', $sale) }}" style="display:none">
@csrf @method('DELETE')
</form>

@include('partials.confirm-modal')

<div class="row g-3">
<!-- Sale Info -->
<div class="col-12 col-md-4">
<div class="card mb-3">
<div class="card-header"><i class="fa fa-info-circle me-2 text-primary"></i>Sale Details</div>
<div class="card-body">
<table class="table table-sm table-borderless mb-0 small">
    <tr><th class="text-muted fw-normal">Invoice #</th><td class="fw-bold">{{ $sale->invoice_number }}</td></tr>
    <tr><th class="text-muted fw-normal">Date</th><td>{{ $sale->sale_date->format('M d, Y') }}</td></tr>
    <tr><th class="text-muted fw-normal">Customer</th><td>{{ $sale->customer_name }}</td></tr>
    <tr><th class="text-muted fw-normal">Payment</th><td>{{ ucfirst(str_replace('_',' ',$sale->payment_method)) }}</td></tr>
    <tr><th class="text-muted fw-normal">Status</th><td><span class="badge bg-{{ $sale->status_badge }}">{{ ucfirst($sale->status) }}</span></td></tr>
    <tr><th class="text-muted fw-normal">By</th><td>{{ $sale->user->name }}</td></tr>
</table>
<div class="mt-3 d-flex gap-2">
    <a href="{{ route('sales.edit', $sale) }}" class="btn btn-sm btn-outline-primary">
        <i class="fa fa-pen me-1"></i>Edit
    </a>
    <button type="button" class="btn btn-sm btn-outline-danger"
            onclick="confirmModal('Delete sale {{ $sale->invoice_number }}? This will reverse all stock changes and cannot be undone.', function(){ document.getElementById(\'deleteSaleForm\').submit(); }, { title: \'Delete Sale\', icon: \'fa-trash\', iconColor: \'#ef4444\', confirmText: \'Delete\', confirmClass: \'danger\' })">
        <i class="fa fa-trash me-1"></i>Delete
    </button>
</div>
</div>
</div>
<div class="card">
<div class="card-header"><i class="fa fa-sack-dollar me-2 text-primary"></i>Payment Summary</div>
<div class="card-body">
    <div class="d-flex justify-content-between mb-1 small"><span class="text-muted">Subtotal</span><strong>Br {{ number_format($sale->subtotal,2) }}</strong></div>
    @if($sale->discount > 0)
    <div class="d-flex justify-content-between mb-1 small"><span class="text-muted">Discount</span><span class="text-danger">-Br {{ number_format($sale->discount,2) }}</span></div>
    @endif
    @if($sale->tax > 0)
    <div class="d-flex justify-content-between mb-1 small"><span class="text-muted">Tax</span><span>+Br {{ number_format($sale->tax,2) }}</span></div>
    @endif
    <hr class="my-2">
    <div class="d-flex justify-content-between mb-1"><span class="fw-bold">Total</span><strong class="text-primary fs-5">Br {{ number_format($sale->total,2) }}</strong></div>
    <div class="d-flex justify-content-between mb-1 small"><span class="text-muted">Paid</span><span class="text-success">Br {{ number_format($sale->paid_amount,2) }}</span></div>
    @if($sale->balance > 0)
    <div class="d-flex justify-content-between mb-1 small"><span class="text-muted">Balance</span><span class="text-danger fw-semibold">Br {{ number_format($sale->balance,2) }}</span></div>
    @endif
    <div class="mt-2">
        <span class="badge bg-{{ $sale->payment_status_badge }} w-100 py-2">{{ ucfirst($sale->payment_status) }}</span>
    </div>
</div>
</div>
</div>

<!-- Items + Returns -->
<div class="col-12 col-md-8">
<div class="card mb-3">
<div class="card-header"><i class="fa fa-list me-2 text-primary"></i>Items Sold ({{ $sale->items->count() }})</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>#</th><th>Item</th><th>Type</th><th>PO #</th><th>Price</th><th>Qty</th><th>Disc</th><th>Total</th></tr></thead>
    <tbody>
        @foreach($sale->items as $i => $item)
        @php
            $poNumber = $item->purchaseItem?->purchase?->purchase_number;
        @endphp
        <tr>
            <td class="text-muted">{{ $i+1 }}</td>
            <td>
                <div class="fw-semibold">{{ $item->item_name }}</div>
                <div class="text-muted small">{{ $item->item_type === 'vehicle' ? $item->vehicleModel?->vehicleType?->name : $item->sparePart?->part_number }}</div>
            </td>
            <td><span class="badge bg-{{ $item->item_type==='vehicle'?'primary':'success' }} bg-opacity-15 text-{{ $item->item_type==='vehicle'?'primary':'success' }}">{{ ucfirst(str_replace('_',' ',$item->item_type)) }}</span></td>
            <td class="small">
                @if($poNumber)
                    <a href="{{ route('purchases.show', $item->purchaseItem->purchase_id) }}" class="text-primary fw-semibold text-decoration-none">
                        {{ $poNumber }}
                    </a>
                @else
                    <span class="text-muted">—</span>
                @endif
            </td>
            <td>Br {{ number_format($item->unit_price,2) }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ $item->discount > 0 ? 'Br '.number_format($item->discount,2) : '—' }}</td>
            <td class="fw-semibold">Br {{ number_format($item->total,2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>
</div>

@if($sale->returns->count())
<div class="card">
<div class="card-header"><i class="fa fa-rotate-left me-2 text-warning"></i>Returns ({{ $sale->returns->count() }})</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Return #</th><th>Date</th><th>Type</th><th>Amount</th><th>Status</th></tr></thead>
    <tbody>
        @foreach($sale->returns as $ret)
        <tr>
            <td><a href="{{ route('sales.returns.show',$ret) }}" class="text-primary">{{ $ret->return_number }}</a></td>
            <td class="text-muted small">{{ $ret->return_date->format('M d, Y') }}</td>
            <td><span class="badge bg-warning text-dark">{{ ucfirst($ret->return_type) }}</span></td>
            <td>Br {{ number_format($ret->total_amount,2) }}</td>
            <td><span class="badge bg-{{ $ret->status_badge }}">{{ ucfirst($ret->status) }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>
</div>
@endif
</div>
</div>
@endsection
