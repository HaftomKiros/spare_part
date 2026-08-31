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
    <div class="d-flex justify-content-between mb-1"><span class="fw-bold">Total Paid to Supplier</span><strong class="text-primary fs-5">Br {{ number_format($purchase->total,2) }}</strong></div>
    <div class="d-flex justify-content-between mb-1 small"><span class="text-muted">Paid</span><span class="text-success">Br {{ number_format($purchase->paid_amount,2) }}</span></div>
    @if($purchase->balance > 0)
    <div class="d-flex justify-content-between mb-1 small"><span class="text-muted">Balance Owed</span><span class="text-danger fw-semibold">Br {{ number_format($purchase->balance,2) }}</span></div>
    @endif
    <div class="mt-2">
        <span class="badge bg-{{ $purchase->payment_status_badge }} w-100 py-2">{{ ucfirst($purchase->payment_status) }}</span>
    </div>

    @php
        // Compute transfer stats from $transferHistory (already loaded by controller)
        // Group by item: source_purchase_item_id → transferred_qty, sold_at_dest
        $transferByItem = $transferHistory->groupBy(fn($t) => $t->item_type . ':' . ($t->spare_part_id ?? $t->vehicle_model_id));

        // Total transferred value and sold-at-destination from transfers
        $totalTransferredValue = $transferHistory->sum(fn($t) => $t->transferred_qty * $t->unit_price);
        $totalSoldAtDest       = $transferHistory->sum('sold_at_dest');
        $totalSoldAtDestValue  = $transferHistory->sum(fn($t) => $t->sold_at_dest * $t->unit_price);

        // Remaining = original total - transferred value - direct sales value
        $directSoldValue   = $purchase->items->sum(fn($item) => $item->total_sold * $item->unit_price);
        $remainingValue    = max(0, $purchase->total - $totalTransferredValue - $directSoldValue);
    @endphp
    <hr class="my-2">
    @if($totalTransferredValue > 0)
    <div class="d-flex justify-content-between mb-1 small">
        <span class="text-muted">Transferred to Other Warehouses</span>
        <span class="text-warning fw-semibold">
            <i class="fa fa-right-left me-1" style="font-size:.7rem"></i>Br {{ number_format($totalTransferredValue, 2) }}
        </span>
    </div>
    <div class="d-flex justify-content-between mb-1 small">
        <span class="text-muted" style="padding-left:1rem">↳ Sold at Destination</span>
        <span class="text-danger">Br {{ number_format($totalSoldAtDestValue, 2) }}</span>
    </div>
    @endif
    @if($directSoldValue > 0)
    <div class="d-flex justify-content-between mb-1 small">
        <span class="text-muted">Sold Directly (this warehouse)</span>
        <span class="text-danger fw-semibold">-Br {{ number_format($directSoldValue, 2) }}</span>
    </div>
    @endif
    <div class="d-flex justify-content-between mb-1">
        <span class="fw-bold">Remaining Stock Value</span>
        <strong class="{{ $remainingValue > 0 ? 'text-success' : 'text-muted' }}">Br {{ number_format($remainingValue, 2) }}</strong>
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
    <thead><tr><th>#</th><th>Item</th><th>Type</th><th>Vehicle Model</th><th>Unit Cost</th><th>Orig Qty</th><th>Transferred</th><th>Sold (Direct)</th><th>Remaining</th><th>Disc</th><th>Total</th></tr></thead>
    <tbody>
        @foreach($purchase->items as $i => $item)
        @php
            $itemKey = $item->item_type . ':' . ($item->item_type === 'spare_part' ? $item->spare_part_id : $item->vehicle_model_id);
            $itemTransfers   = $transferByItem[$itemKey] ?? collect();
            $transferredQty  = $itemTransfers->sum('transferred_qty');
            $soldAtDestQty   = $itemTransfers->sum('sold_at_dest');
            $origQty         = $item->quantity + $transferredQty;   // original = current + transferred
            $remaining       = max(0, $item->quantity - $item->total_sold);
            $isFullySold     = $remaining === 0 && $item->quantity > 0;
            $isPartial       = $item->total_sold > 0 && $remaining > 0;
        @endphp
        <tr>
            <td class="text-muted">{{ $i+1 }}</td>
            <td>
                <div class="fw-semibold">{{ $item->item_name }}</div>
                <div class="text-muted small">{{ $item->item_type === 'vehicle' ? $item->vehicleModel?->vehicleType?->name : $item->sparePart?->part_number }}</div>
            </td>
            <td><span class="badge bg-{{ $item->item_type==='vehicle'?'primary':'success' }} bg-opacity-15 text-{{ $item->item_type==='vehicle'?'primary':'success' }}">{{ ucfirst(str_replace('_',' ',$item->item_type)) }}</span></td>
            <td class="small" style="max-width:150px">
                @if($item->item_type === 'vehicle')
                    <span style="color:#3730a3;font-weight:600">{{ $item->vehicleModel?->brand }} {{ $item->vehicleModel?->model_name }}</span>
                @else
                    @php $vms = $item->sparePart?->compatibleVehicles; @endphp
                    @if($vms && $vms->count() > 0)
                        <span style="color:#3730a3;font-size:.8rem">{{ $vms->first()->brand }} {{ $vms->first()->model_name }}</span>
                        @if($vms->count() > 1)<span class="text-muted" style="font-size:.7rem">, +{{ $vms->count()-1 }} more</span>@endif
                    @else
                        <span class="text-muted">—</span>
                    @endif
                @endif
            </td>
            <td>Br {{ number_format($item->unit_price,2) }}</td>
            <td class="fw-semibold">{{ $origQty }}</td>
            <td>
                @if($transferredQty > 0)
                    <span class="text-warning fw-semibold">
                        <i class="fa fa-right-left me-1" style="font-size:.7rem"></i>{{ $transferredQty }}
                    </span>
                    @if($soldAtDestQty > 0)
                        <div class="text-muted" style="font-size:.72rem">{{ $soldAtDestQty }} sold at dest</div>
                    @endif
                @else
                    <span class="text-muted">—</span>
                @endif
            </td>
            <td>
                <span class="fw-semibold {{ $item->total_sold > 0 ? 'text-danger' : 'text-muted' }}">
                    {{ $item->total_sold > 0 ? $item->total_sold : '—' }}
                </span>
            </td>
            <td>
                <span class="fw-semibold {{ $isFullySold ? 'text-danger' : ($isPartial ? 'text-warning' : 'text-success') }}">
                    {{ $remaining }}
                </span>
            </td>
            <td>{{ $item->discount > 0 ? 'Br '.number_format($item->discount,2) : '—' }}</td>
            <td class="fw-semibold">Br {{ number_format($origQty * $item->unit_price,2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="table-light">
            <td colspan="10" class="text-end fw-bold">Original Total</td>
            <td class="fw-bold text-primary fs-6">Br {{ number_format($purchase->total,2) }}</td>
        </tr>
        @if($totalTransferredValue > 0)
        <tr class="table-light">
            <td colspan="10" class="text-end fw-semibold text-warning">
                <i class="fa fa-right-left me-1"></i>Transferred Value
            </td>
            <td class="fw-semibold text-warning">Br {{ number_format($totalTransferredValue, 2) }}</td>
        </tr>
        @endif
        <tr class="table-light">
            <td colspan="10" class="text-end fw-semibold text-success">Remaining Stock Value</td>
            <td class="fw-bold text-success">Br {{ number_format($remainingValue, 2) }}</td>
        </tr>
    </tfoot>
</table>
</div>
</div>
</div>
</div>

{{-- Transfer History --}}
@if($transferHistory->count())
<div class="row g-3 mt-0">
<div class="col-12">
<div class="card">
<div class="card-header d-flex align-items-center gap-2">
    <i class="fa fa-right-left text-warning me-1"></i>
    <span>Transfer History</span>
    <span class="badge bg-warning-subtle text-warning-emphasis ms-1">{{ $transferHistory->count() }} transfer{{ $transferHistory->count() != 1 ? 's' : '' }}</span>
</div>
<div class="table-responsive">
<table class="table table-sm mb-0" style="font-size:.85rem">
    <thead style="background:#f8f9ff">
        <tr>
            <th class="ps-3">Transfer #</th>
            <th>Item</th>
            <th>To Warehouse</th>
            <th class="text-center">Qty Transferred</th>
            <th class="text-center">Sold at Dest</th>
            <th class="text-center">Still Available</th>
            <th class="text-end">Value</th>
            <th>By</th>
            <th class="pe-3">Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transferHistory as $tr)
        @php
            $itemName = $tr->item_type === 'spare_part'
                ? (\App\Models\SparePart::find($tr->spare_part_id)?->name ?? '—')
                : (\App\Models\VehicleModel::find($tr->vehicle_model_id)?->full_name ?? '—');
            $stillAvailable = $tr->transferred_qty - $tr->sold_at_dest;
        @endphp
        <tr>
            <td class="ps-3">
                <span class="fw-semibold" style="color:var(--brand-1)">{{ $tr->transfer_number }}</span>
            </td>
            <td class="fw-semibold small">{{ $itemName }}</td>
            <td class="small">
                <i class="fa fa-arrow-right text-warning me-1" style="font-size:.7rem"></i>{{ $tr->to_warehouse }}
            </td>
            <td class="text-center">
                <span class="badge bg-warning-subtle text-warning-emphasis fw-bold">{{ $tr->transferred_qty }}</span>
            </td>
            <td class="text-center text-danger fw-semibold">{{ $tr->sold_at_dest }}</td>
            <td class="text-center">
                <span class="badge {{ $stillAvailable > 0 ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis' }}">
                    {{ $stillAvailable }}
                </span>
            </td>
            <td class="text-end fw-semibold" style="color:var(--brand-1)">
                Br {{ number_format($tr->transferred_qty * $tr->unit_price, 2) }}
            </td>
            <td class="text-muted small">{{ $tr->transferred_by }}</td>
            <td class="text-muted small pe-3">
                {{ \Carbon\Carbon::parse($tr->transferred_at)->format('M d, Y H:i') }}
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot style="background:#f8f9ff">
        <tr>
            <td colspan="3" class="text-end fw-bold ps-3 small">Total Transferred</td>
            <td class="text-center fw-bold text-warning">{{ $transferHistory->sum('transferred_qty') }}</td>
            <td class="text-center fw-bold text-danger">{{ $transferHistory->sum('sold_at_dest') }}</td>
            <td class="text-center fw-bold text-success">{{ $transferHistory->sum('transferred_qty') - $transferHistory->sum('sold_at_dest') }}</td>
            <td class="text-end fw-bold" style="color:var(--brand-1)">
                Br {{ number_format($transferHistory->sum(fn($t) => $t->transferred_qty * $t->unit_price), 2) }}
            </td>
            <td colspan="2" class="pe-3"></td>
        </tr>
    </tfoot>
</table>
</div>
</div>
</div>
</div>
@endif

@endsection
