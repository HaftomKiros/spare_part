@extends('layouts.app')
@section('title', 'Return '.$return->return_number)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('sales.returns.index') }}">Returns</a></li>
    <li class="breadcrumb-item active">{{ $return->return_number }}</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'Return: '.$return->return_number,'subtitle'=>$return->return_date?->format('M d, Y') ?? 'N/A'])

<div class="row g-3">
<div class="col-12 col-md-4">
<div class="card">
<div class="card-header"><i class="fa fa-info-circle me-2 text-primary"></i>Return Details</div>
<div class="card-body">
<table class="table table-sm table-borderless mb-0 small">
    <tr><th class="text-muted fw-normal">Return #</th><td class="fw-bold">{{ $return->return_number }}</td></tr>
    <tr><th class="text-muted fw-normal">Invoice</th>
        <td>
            @if($return->sale)
                <a href="{{ route('sales.show',$return->sale) }}" class="text-primary">{{ $return->sale->invoice_number }}</a>
            @else
                <span class="text-muted fst-italic">Sale deleted</span>
            @endif
        </td>
    </tr>
    <tr><th class="text-muted fw-normal">Customer</th><td>{{ $return->customer?->name ?? 'Walk-in' }}</td></tr>
    <tr><th class="text-muted fw-normal">Date</th><td>{{ $return->return_date?->format('M d, Y') ?? 'N/A' }}</td></tr>
    <tr><th class="text-muted fw-normal">Type</th><td><span class="badge bg-warning text-dark">{{ ucfirst($return->return_type) }}</span></td></tr>
    <tr><th class="text-muted fw-normal">Total</th><td class="fw-bold text-danger">Br {{ number_format($return->total_amount,2) }}</td></tr>
    <tr><th class="text-muted fw-normal">Status</th><td><span class="badge bg-{{ $return->status_badge }}">{{ ucfirst($return->status) }}</span></td></tr>
    <tr><th class="text-muted fw-normal">By</th><td>{{ $return->user?->name ?? 'Deleted user' }}</td></tr>
    @if($return->reason)
    <tr><th class="text-muted fw-normal">Reason</th><td>{{ $return->reason }}</td></tr>
    @endif
</table>
</div>
</div>
</div>

<div class="col-12 col-md-8">
<div class="card">
<div class="card-header"><i class="fa fa-list me-2 text-primary"></i>Returned Items</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>#</th><th>Item</th><th>Type</th><th>Unit Price</th><th>Qty</th><th>Total</th></tr></thead>
    <tbody>
        @foreach($return->items as $i => $item)
        <tr>
            <td class="text-muted">{{ $i+1 }}</td>
            <td>
                <div class="fw-semibold">{{ $item->item_name }}</div>
                <div class="text-muted small">{{ $item->item_type === 'spare_part' ? $item->sparePart?->part_number : $item->vehicleModel?->vehicleType?->name }}</div>
            </td>
            <td><span class="badge bg-{{ $item->item_type==='vehicle'?'primary':'success' }} bg-opacity-15 text-{{ $item->item_type==='vehicle'?'primary':'success' }}">{{ ucfirst(str_replace('_',' ',$item->item_type)) }}</span></td>
            <td>Br {{ number_format($item->unit_price,2) }}</td>
            <td>{{ $item->quantity }}</td>
            <td class="fw-semibold">Br {{ number_format($item->total,2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" class="text-end fw-bold">Total Refunded</td>
            <td class="fw-bold text-danger fs-6">Br {{ number_format($return->total_amount,2) }}</td>
        </tr>
    </tfoot>
</table>
</div>
</div>
</div>
</div>
@endsection
