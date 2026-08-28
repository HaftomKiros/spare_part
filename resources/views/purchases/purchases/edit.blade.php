@extends('layouts.app')
@section('title', 'Edit Purchase '.$purchase->purchase_number)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchases.show', $purchase) }}">{{ $purchase->purchase_number }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection
@section('content')
@include('partials.page-header', ['title' => 'Edit Purchase: '.$purchase->purchase_number, 'subtitle' => 'Update payment details only'])

<form method="POST" action="{{ route('purchases.update', $purchase) }}">
@csrf @method('PUT')
<div class="row g-3 justify-content-center">
<div class="col-12 col-lg-8">

<div class="alert alert-info small mb-3">
    <i class="fa fa-circle-info me-1"></i>
    Items and prices cannot be changed after a purchase is received. Only payment details and notes can be updated.
</div>

<div class="card mb-3">
<div class="card-header"><i class="fa fa-file-invoice me-2 text-primary"></i>Purchase Information</div>
<div class="card-body">
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">PO #</label>
        <input type="text" class="form-control" value="{{ $purchase->purchase_number }}" disabled>
    </div>
    <div class="col-md-4">
        <label class="form-label">Supplier</label>
        <input type="text" class="form-control" value="{{ $purchase->supplier?->name ?? 'Transfer (no supplier)' }}" disabled>
    </div>
    <div class="col-md-4">
        <label class="form-label">Warehouse</label>
        <input type="text" class="form-control" value="{{ $purchase->warehouse?->name ?? '—' }}" disabled>
    </div>
    <div class="col-md-4">
        <label class="form-label">Purchase Date <span class="text-danger">*</span></label>
        <input type="date" name="purchase_date" class="form-control @error('purchase_date') is-invalid @enderror"
               value="{{ old('purchase_date', $purchase->purchase_date->format('Y-m-d')) }}" required>
        @error('purchase_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Due Date</label>
        <input type="date" name="due_date" class="form-control"
               value="{{ old('due_date', $purchase->due_date?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Amount Paid (Br) <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">Br</span>
            <input type="number" name="paid_amount" class="form-control @error('paid_amount') is-invalid @enderror"
                   value="{{ old('paid_amount', $purchase->paid_amount) }}" min="0" step="0.01" required>
        </div>
        <div class="form-text">Total: Br {{ number_format($purchase->total, 2) }}</div>
        @error('paid_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $purchase->notes) }}</textarea>
    </div>
</div>
</div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary px-4">
        <i class="fa fa-save me-1"></i>Save Changes
    </button>
    <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>

</div>
</div>
</form>
@endsection
