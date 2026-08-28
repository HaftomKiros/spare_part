@extends('layouts.app')
@section('title','Purchase '.$purchase->purchase_number)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
    <li class="breadcrumb-item active">{{ $purchase->purchase_number }}</li>
@endsection
@section('content')
@include('partials.page-header',[
    'title'   =>'Purchase: '.$purchase->purchase_number,
    'subtitle'=>$purchase->purchase_date->format('M d, Y'),
])

{{-- Delete form --}}
<form id="deletePurchaseForm" method="POST" action="{{ route('purchases.destroy', $purchase) }}" style="display:none">
@csrf @method('DELETE')
</form>

@include('partials.confirm-modal')

@push('scripts')
<script>
(function() {
    var btnDelete = document.getElementById('btnDeletePurchase');
    if (btnDelete) {
        btnDelete.addEventListener('click', function() {
            confirmModal(
                'Delete purchase {{ $purchase->purchase_number }}? This will reverse all stock changes and cannot be undone.',
                function() { document.getElementById('deletePurchaseForm').submit(); },
                { title: 'Delete Purchase', icon: 'fa-trash', iconColor: '#ef4444', confirmText: 'Delete', confirmClass: 'danger' }
            );
        });
    }

    var btnReceive = document.getElementById('btnReceivePurchase');
    if (btnReceive) {
        btnReceive.addEventListener('click', function() {
            confirmModal(
                'Mark {{ $purchase->purchase_number }} as received and update stock?',
                function() { document.getElementById('receiveForm').submit(); },
                { title: 'Mark Received', icon: 'fa-check', iconColor: '#16a34a', confirmText: 'Mark Received', confirmClass: 'success' }
            );
        });
    }
})();
</script>
@endpush

<div class="row g-3">
<!-- Info Cards -->
<div class="col-12 col-md-4">

<div class="card mb-3">
<div class="card-header"><i class="fa fa-info-circle me-2 text-primary"></i>Purchase Details</div>
<div class="card-body">
<table class="table table-sm table-borderless mb-0 small">
    <tr><th class="text-muted fw-normal">PO #</th><td class="fw-bold">{{ $purchase->purchase_number }}</td></tr>
    <tr><th class="text-muted fw-normal">Supplier</th>
        <td>
            @if($purchase->supplier)
                <a href="{{ route('purchases.suppliers.show',$purchase->supplier) }}" class="text-primary">{{ $purchase->supplier->name }}</a>
            @else
                <span class="text-muted fst-italic">Transfer (no supplier)</span>
            @endif
        </td>
    </tr>
    <tr><th class="text-muted fw-normal">Warehouse</th>
        <td class="fw-semibold">{{ $purchase->warehouse?->name ?? '—' }}</td>
    </tr>
    <tr><th class="text-muted fw-normal">Date</th><td>{{ $purchase->purchase_date->format('M d, Y') }}</td></tr>
    <tr><th class="text-muted fw-normal">Due Date</th>
        <td class="{{ $purchase->due_date && $purchase->due_date->isPast() && $purchase->payment_status !== 'paid' ? 'text-danger' : '' }}">
            {{ $purchase->due_date ? $purchase->due_date->format('M d, Y') : '—' }}
        </td>
    </tr>
    <tr><th class="text-muted fw-normal">Status</th>
        <td><span class="badge bg-{{ $purchase->status_badge }}">{{ ucfirst($purchase->status) }}</span></td>
    </tr>
    <tr><th class="text-muted fw-normal">By</th><td>{{ $purchase->user->name }}</td></tr>
    @if($purchase->notes)
    <tr><th class="text-muted fw-normal">Notes</th><td>{{ $purchase->notes }}</td></tr>
    @endif
</table>
<div class="mt-3 d-flex gap-2">
    <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-sm btn-outline-primary">
        <i class="fa fa-pen me-1"></i>Edit
    </a>
    <button type="button" class="btn btn-sm btn-outline-danger" id="btnDeletePurchase">
        <i class="fa fa-trash me-1"></i>Delete
    </button>
</div>
</div>
</div>

<div class="card">
<div class="card-header"><i class="fa fa-sack-dollar me-2 text-primary"></i>Payment Summary</div>
<div class="card-body">
    <div class="d-flex justify-content-between mb-1 small"><span class="text-muted">Subtotal</span><strong>Br {{ number_format($purchase->subtotal,2) }}</strong></div>
    @if($purchase->discount > 0)
    <div class="d-flex justify-content-between mb-1 small"><span class="text-muted">Discount</span><span class="text-danger">-Br {{ number_format($purchase->discount,2) }}</span></div>
    @endif
    @if($purchase->tax > 0)
    <div class="d-flex justify-content-between mb-1 small"><span class="text-muted">Tax</span><span>+Br {{ number_format($purchase->tax,2) }}</span></div>
    @endif
    <hr class="my-2">
    <div class="d-flex justify-content-between mb-1"><span class="fw-bold">Total</span><strong class="text-primary fs-5">Br {{ number_format($purchase->total,2) }}</strong></div>
    <div class="d-flex justify-content-between mb-1 small"><span class="text-muted">Paid</span><span class="text-success">Br {{ number_format($purchase->paid_amount,2) }}</span></div>
    @if($purchase->balance > 0)
    <div class="d-flex justify-content-between mb-1 small"><span class="text-muted">Balance Owed</span><span class="text-danger fw-semibold">Br {{ number_format($purchase->balance,2) }}</span></div>
    @endif
    <div class="mt-2">
        <span class="badge bg-{{ $purchase->payment_status_badge }} w-100 py-2">{{ ucfirst($purchase->payment_status) }}</span>
    </div>
</div>
</div>

</div>

<!-- Items -->
<div class="col-12 col-md-8">
<div class="card">
<div class="card-header">
    <span><i class="fa fa-list me-2 text-primary"></i>Items Purchased ({{ $purchase->items->count() }})</span>
    <div class="d-flex align-items-center gap-2">
        @if($purchase->status === 'received' && $purchase->items->sum(fn($i) => $i->quantity - $i->total_sold) > 0)
        <a href="{{ route('sales.create', ['po_number' => $purchase->purchase_number, 'warehouse_id' => $purchase->warehouse_id]) }}"
           class="btn btn-sm btn-outline-success">
            <i class="fa fa-cart-plus me-1"></i>Sell from this PO
        </a>
        @endif
        @if($purchase->status !== 'received')
        <form id="receiveForm" method="POST" action="{{ route('purchases.receive',$purchase) }}" class="d-inline">
            @csrf
            <button type="button" class="btn btn-sm btn-success" id="btnReceivePurchase">
                <i class="fa fa-check me-1"></i>Mark Received
            </button>
        </form>
        @else
        <span class="badge bg-success"><i class="fa fa-check me-1"></i>Stock Updated</span>
        @endif
    </div>
</div>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>#</th><th>Item</th><th>Type</th><th>Unit Cost</th><th>Qty</th><th>Total Sold</th><th>Remaining</th><th>Disc</th><th>Total</th></tr></thead>
    <tbody>
        @foreach($purchase->items as $i => $item)
        @php
            $remaining = max(0, $item->quantity - $item->total_sold);
            $isFullySold = $remaining === 0 && $item->quantity > 0;
            $isPartial   = $item->total_sold > 0 && $remaining > 0;
        @endphp
        <tr>
            <td class="text-muted">{{ $i+1 }}</td>
            <td>
                <div class="fw-semibold">{{ $item->item_name }}</div>
                <div class="text-muted small">{{ $item->item_type === 'vehicle' ? $item->vehicleModel?->vehicleType?->name : $item->sparePart?->part_number }}</div>
            </td>
            <td><span class="badge bg-{{ $item->item_type==='vehicle'?'primary':'success' }} bg-opacity-15 text-{{ $item->item_type==='vehicle'?'primary':'success' }}">{{ ucfirst(str_replace('_',' ',$item->item_type)) }}</span></td>
            <td>Br {{ number_format($item->unit_price,2) }}</td>
            <td class="fw-semibold">{{ $item->quantity }}</td>
            <td>
                <span class="fw-semibold {{ $item->total_sold > 0 ? 'text-danger' : 'text-muted' }}">
                    {{ $item->total_sold }}
                </span>
            </td>
            <td>
                <span class="fw-semibold {{ $isFullySold ? 'text-danger' : ($isPartial ? 'text-warning' : 'text-success') }}">
                    {{ $remaining }}
                </span>
            </td>
            <td>{{ $item->discount > 0 ? 'Br '.number_format($item->discount,2) : '—' }}</td>
            <td class="fw-semibold">Br {{ number_format($item->total,2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="table-light">
            <td colspan="8" class="text-end fw-bold">Grand Total</td>
            <td class="fw-bold text-primary fs-6">Br {{ number_format($purchase->total,2) }}</td>
        </tr>
    </tfoot>
</table>
</div>
</div>
</div>
</div>
@endsection
