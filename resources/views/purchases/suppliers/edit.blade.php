@extends('layouts.app')
@section('title','Edit Supplier')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('purchases.suppliers.index') }}">Suppliers</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'Edit Supplier','subtitle'=>$supplier->name])
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
<div class="card">
<div class="card-header"><i class="fa fa-pen me-2 text-primary"></i>Edit Details</div>
<div class="card-body">
<form method="POST" action="{{ route('purchases.suppliers.update',$supplier) }}">
@csrf @method('PUT')
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Supplier Code</label>
        <input type="text" class="form-control" value="{{ $supplier->supplier_code }}" readonly>
    </div>
    <div class="col-md-8">
        <label class="form-label">Supplier Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name',$supplier->name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Company</label>
        <input type="text" name="company" class="form-control" value="{{ old('company',$supplier->company) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Contact Person</label>
        <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person',$supplier->contact_person) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone <span class="text-danger">*</span></label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone',$supplier->phone) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email',$supplier->email) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">City</label>
        <input type="text" name="city" class="form-control" value="{{ old('city',$supplier->city) }}">
    </div>
    <div class="col-12">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2">{{ old('address',$supplier->address) }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes',$supplier->notes) }}</textarea>
    </div>
    <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="active"   {{ old('status',$supplier->status)==='active'?'selected':'' }}>Active</option>
            <option value="inactive" {{ old('status',$supplier->status)==='inactive'?'selected':'' }}>Inactive</option>
        </select>
    </div>
</div>
<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary px-4"><i class="fa fa-save me-1"></i>Update</button>
    <a href="{{ route('purchases.suppliers.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>
</form>
</div>
</div>
</div>
</div>
@endsection
