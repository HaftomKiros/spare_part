@extends('layouts.app')
@section('title','Edit Warehouse')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('settings.warehouses.index') }}">Warehouses</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'Edit Warehouse','subtitle'=>$warehouse->name])
<div class="row justify-content-center">
<div class="col-12 col-lg-7">
<div class="card">
<div class="card-header"><i class="fa fa-pen me-2" style="color:var(--brand-1)"></i>Edit Details</div>
<div class="card-body">
<form method="POST" action="{{ route('settings.warehouses.update', $warehouse) }}">
@csrf @method('PUT')
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Code</label>
        <input type="text" class="form-control" value="{{ $warehouse->code }}" readonly>
    </div>
    <div class="col-md-8">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name',$warehouse->name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">City</label>
        <input type="text" name="city" class="form-control" value="{{ old('city',$warehouse->city) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone',$warehouse->phone) }}">
    </div>
    <div class="col-12">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2">{{ old('address',$warehouse->address) }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label">Manager</label>
        <input type="text" name="manager" class="form-control" value="{{ old('manager',$warehouse->manager) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="active"   {{ old('status',$warehouse->status)==='active'?'selected':'' }}>Active</option>
            <option value="inactive" {{ old('status',$warehouse->status)==='inactive'?'selected':'' }}>Inactive</option>
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes',$warehouse->notes) }}</textarea>
    </div>
    <div class="col-12">
        <div class="form-check">
            <input type="checkbox" name="is_default" value="1" class="form-check-input" id="isDefault"
                   {{ old('is_default',$warehouse->is_default) ? 'checked' : '' }}>
            <label class="form-check-label" for="isDefault">Set as <strong>default warehouse</strong></label>
        </div>
    </div>
</div>
<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary px-4"><i class="fa fa-save me-1"></i>Update</button>
    <a href="{{ route('settings.warehouses.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>
</form>
</div>
</div>
</div>
</div>
@endsection
