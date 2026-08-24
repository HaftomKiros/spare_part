@extends('layouts.app')
@section('title','Add Warehouse')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('settings.warehouses.index') }}">Warehouses</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'Add Warehouse','subtitle'=>'Register a new stock location'])

<div class="row justify-content-center">
<div class="col-12 col-lg-7">
<div class="card">
<div class="card-header"><i class="fa fa-warehouse me-2" style="color:var(--brand-1)"></i>Warehouse Details</div>
<div class="card-body">
<form method="POST" action="{{ route('settings.warehouses.store') }}">
@csrf
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Code</label>
        <input type="text" class="form-control" value="{{ $code }}" readonly>
    </div>
    <div class="col-md-8">
        <label class="form-label">Warehouse Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name') }}" placeholder="e.g. Mekelle Branch, Addis Main Store" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">City</label>
        <input type="text" name="city" class="form-control" value="{{ old('city') }}" placeholder="e.g. Mekelle">
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
    </div>
    <div class="col-12">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label">Manager Name</label>
        <input type="text" name="manager" class="form-control" value="{{ old('manager') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="active">Active</option>
            <option value="inactive" {{ old('status')==='inactive'?'selected':'' }}>Inactive</option>
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
    </div>
    <div class="col-12">
        <div class="form-check">
            <input type="checkbox" name="is_default" value="1" class="form-check-input" id="isDefault"
                   {{ old('is_default') ? 'checked' : '' }}>
            <label class="form-check-label" for="isDefault">
                Set as <strong>default warehouse</strong>
                <span class="text-muted small">(used when no warehouse is specified in sales/purchases)</span>
            </label>
        </div>
    </div>
</div>
<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary px-4"><i class="fa fa-save me-1"></i>Save Warehouse</button>
    <a href="{{ route('settings.warehouses.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>
</form>
</div>
</div>
</div>
</div>
@endsection
