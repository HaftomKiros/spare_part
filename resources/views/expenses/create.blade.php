@extends('layouts.app')
@section('title','New Expense')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('expenses.index') }}">Expenses</a></li>
    <li class="breadcrumb-item active">New</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'Record New Expense','subtitle'=>$number])

<div class="row justify-content-center">
<div class="col-12 col-lg-8">
<div class="card">
<div class="card-header">
    <i class="fa fa-money-bill-wave me-2 text-danger"></i>Expense Details
</div>
<div class="card-body">
<form method="POST" action="{{ route('expenses.store') }}">
@csrf
<div class="row g-3">

    <div class="col-md-6">
        <label class="form-label">Category <span class="text-danger">*</span></label>
        <select name="expense_category_id"
                class="form-select ts-select @error('expense_category_id') is-invalid @enderror"
                required>
            <option value="">Select category…</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}"
                    {{ old('expense_category_id') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
        @error('expense_category_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">
            <a href="{{ route('expense-categories.index') }}" target="_blank">
                <i class="fa fa-plus me-1"></i>Manage categories
            </a>
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label">Title / Description <span class="text-danger">*</span></label>
        <input type="text" name="title"
               class="form-control @error('title') is-invalid @enderror"
               value="{{ old('title') }}"
               placeholder="e.g. August Salary — Mekelle Staff"
               required>
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Amount (Br) <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">Br</span>
            <input type="number" name="amount"
                   class="form-control @error('amount') is-invalid @enderror"
                   value="{{ old('amount') }}" min="0.01" step="0.01" required>
        </div>
        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Date <span class="text-danger">*</span></label>
        <input type="date" name="expense_date"
               class="form-control @error('expense_date') is-invalid @enderror"
               value="{{ old('expense_date', today()->format('Y-m-d')) }}" required>
        @error('expense_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
        <select name="payment_method" class="form-select ts-select" required>
            <option value="cash"          {{ old('payment_method','cash')==='cash'?'selected':'' }}>Cash</option>
            <option value="bank_transfer" {{ old('payment_method')==='bank_transfer'?'selected':'' }}>Bank Transfer</option>
            <option value="cheque"        {{ old('payment_method')==='cheque'?'selected':'' }}>Cheque</option>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Warehouse</label>
        <select name="warehouse_id" class="form-select ts-select">
            <option value="">Company-wide (not warehouse specific)</option>
            @foreach($warehouses as $w)
                <option value="{{ $w->id }}"
                    {{ old('warehouse_id') == $w->id ? 'selected' : '' }}>
                    {{ $w->name }}{{ $w->city ? ' — '.$w->city : '' }}
                </option>
            @endforeach
        </select>
        <div class="form-text">Leave empty for company-wide expenses like head office rent.</div>
    </div>

    <div class="col-md-6">
        <label class="form-label">Reference / Receipt #</label>
        <input type="text" name="reference_number" class="form-control"
               value="{{ old('reference_number') }}"
               placeholder="Optional receipt or voucher number">
    </div>

    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2"
                  placeholder="Optional additional details…">{{ old('notes') }}</textarea>
    </div>

</div>
<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary px-4">
        <i class="fa fa-save me-1"></i>Save Expense
    </button>
    <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>
</form>
</div>
</div>
</div>
</div>
@endsection
