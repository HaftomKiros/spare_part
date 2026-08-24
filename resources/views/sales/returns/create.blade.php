@extends('layouts.app')
@section('title','New Return')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('sales.returns.index') }}">Returns</a></li>
    <li class="breadcrumb-item active">New</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'New Sale Return','subtitle'=>$number])

<form method="POST" action="{{ route('sales.returns.store') }}" id="returnForm">
@csrf
<div class="row g-3">

<div class="col-12 col-lg-8">
<div class="card mb-3">
<div class="card-header"><i class="fa fa-rotate-left me-2 text-warning"></i>Return Details</div>
<div class="card-body">
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Return Number</label>
        <input type="text" class="form-control" value="{{ $number }}" readonly>
    </div>
    <div class="col-md-4">
        <label class="form-label">Original Invoice <span class="text-danger">*</span></label>
        <select name="sale_id" id="saleSelect" class="form-select @error('sale_id') is-invalid @enderror" required>
            <option value="">Select invoice…</option>
            @foreach($sales as $s)
                <option value="{{ $s->id }}" {{ (old('sale_id', $sale?->id) == $s->id) ? 'selected' : '' }}>
                    {{ $s->invoice_number }} — {{ $s->customer_name }} ({{ $s->sale_date->format('M d, Y') }})
                </option>
            @endforeach
        </select>
        @error('sale_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Return Date <span class="text-danger">*</span></label>
        <input type="date" name="return_date" class="form-control"
               value="{{ old('return_date', today()->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Return Type <span class="text-danger">*</span></label>
        <select name="return_type" class="form-select" required>
            <option value="refund"   {{ old('return_type','refund')==='refund'?'selected':'' }}>Refund</option>
            <option value="exchange" {{ old('return_type')==='exchange'?'selected':'' }}>Exchange</option>
            <option value="credit"   {{ old('return_type')==='credit'?'selected':'' }}>Store Credit</option>
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Reason</label>
        <textarea name="reason" class="form-control" rows="2" placeholder="Reason for return…">{{ old('reason') }}</textarea>
    </div>
</div>
</div>
</div>

@if($sale)
<div class="card">
<div class="card-header"><i class="fa fa-list me-2 text-primary"></i>Select Items to Return</div>
<div class="table-responsive">
<table class="table mb-0">
    <thead>
        <tr><th><input type="checkbox" id="selectAll" class="form-check-input"></th><th>Item</th><th>Orig Qty</th><th>Price</th><th>Return Qty</th></tr>
    </thead>
    <tbody>
        @foreach($sale->items as $i => $item)
        <tr>
            <td><input type="checkbox" name="return_items[{{ $i }}][selected]" class="form-check-input item-check" value="1"></td>
            <td>
                <input type="hidden" name="items[{{ $i }}][sale_item_id]" value="{{ $item->id }}">
                <input type="hidden" name="items[{{ $i }}][item_type]" value="{{ $item->item_type }}">
                <input type="hidden" name="items[{{ $i }}][item_id]" value="{{ $item->item_type === 'vehicle' ? $item->vehicle_model_id : $item->spare_part_id }}">
                <input type="hidden" name="items[{{ $i }}][unit_price]" value="{{ $item->unit_price }}">
                <div class="fw-semibold small">{{ $item->item_name }}</div>
                <div class="text-muted" style="font-size:.75rem">{{ $item->item_type === 'spare_part' ? $item->sparePart?->part_number : $item->vehicleModel?->vehicleType?->name }}</div>
            </td>
            <td class="text-muted">{{ $item->quantity }}</td>
            <td class="text-muted">Br {{ number_format($item->unit_price,2) }}</td>
            <td>
                <input type="number" name="items[{{ $i }}][quantity]" class="form-control form-control-sm return-qty"
                       value="{{ $item->quantity }}" min="1" max="{{ $item->quantity }}" style="width:80px" disabled>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>
</div>
@else
<div class="alert alert-info small">
    <i class="fa fa-info-circle me-1"></i>Select an invoice above to load its items.
</div>
@endif
</div>

<div class="col-12 col-lg-4">
<div class="card">
<div class="card-header"><i class="fa fa-circle-info me-2 text-warning"></i>Notes</div>
<div class="card-body">
    <div class="alert alert-warning small py-2">
        <i class="fa fa-triangle-exclamation me-1"></i>
        Approved returns immediately add stock back to inventory.
    </div>
    <div class="d-grid mt-3">
        <button type="submit" class="btn btn-warning">
            <i class="fa fa-save me-1"></i>Process Return
        </button>
    </div>
    <a href="{{ route('sales.returns.index') }}" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
</div>
</div>
</div>

</div>
</form>

@push('scripts')
<script>
// Enable/disable qty inputs based on checkbox
document.querySelectorAll('.item-check').forEach(cb => {
    cb.addEventListener('change', function () {
        const qtyInput = this.closest('tr').querySelector('.return-qty');
        qtyInput.disabled = !this.checked;
    });
});

// Select all
document.getElementById('selectAll')?.addEventListener('change', function () {
    document.querySelectorAll('.item-check').forEach(cb => {
        cb.checked = this.checked;
        cb.dispatchEvent(new Event('change'));
    });
});

// Reload page with sale selected
document.getElementById('saleSelect')?.addEventListener('change', function () {
    if (this.value) {
        window.location.href = '{{ route("sales.returns.create") }}?sale_id=' + this.value;
    }
});
</script>
@endpush
@endsection
