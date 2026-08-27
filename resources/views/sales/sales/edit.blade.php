@extends('layouts.app')
@section('title', 'Edit Sale '.$sale->invoice_number)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales.show', $sale) }}">{{ $sale->invoice_number }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection
@section('content')
@include('partials.page-header', ['title' => 'Edit Sale: '.$sale->invoice_number, 'subtitle' => 'Update payment details only'])

<form method="POST" action="{{ route('sales.update', $sale) }}">
@csrf @method('PUT')
<div class="row g-3 justify-content-center">
<div class="col-12 col-lg-8">

<div class="alert alert-info small mb-3">
    <i class="fa fa-circle-info me-1"></i>
    Items and prices cannot be changed after a sale is completed. Only payment details and notes can be updated.
</div>

<div class="card mb-3">
<div class="card-header"><i class="fa fa-receipt me-2 text-primary"></i>Sale Information</div>
<div class="card-body">
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Invoice #</label>
        <input type="text" class="form-control" value="{{ $sale->invoice_number }}" disabled>
    </div>
    <div class="col-md-4">
        <label class="form-label">Date <span class="text-danger">*</span></label>
        <input type="date" name="sale_date" class="form-control @error('sale_date') is-invalid @enderror"
               value="{{ old('sale_date', $sale->sale_date->format('Y-m-d')) }}" required>
        @error('sale_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Customer</label>
        <select name="customer_id" class="form-select ts-select">
            <option value="">Walk-in Customer</option>
            @foreach($customers as $c)
                <option value="{{ $c->id }}" {{ old('customer_id', $sale->customer_id) == $c->id ? 'selected' : '' }}>
                    {{ $c->name }} ({{ $c->phone }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
        <select name="payment_method" class="form-select ts-select" required>
            @foreach(['cash','bank_transfer','cheque','credit'] as $m)
                <option value="{{ $m }}" {{ old('payment_method', $sale->payment_method) === $m ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_',' ',$m)) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Amount Paid (Br) <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">Br</span>
            <input type="number" name="paid_amount" class="form-control @error('paid_amount') is-invalid @enderror"
                   value="{{ old('paid_amount', $sale->paid_amount) }}" min="0" step="0.01" required>
        </div>
        <div class="form-text">Total: Br {{ number_format($sale->total, 2) }}</div>
        @error('paid_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $sale->notes) }}</textarea>
    </div>
</div>
</div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary px-4">
        <i class="fa fa-save me-1"></i>Save Changes
    </button>
    <a href="{{ route('sales.show', $sale) }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>

</div>
</div>
</form>
@endsection
