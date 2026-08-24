@extends('layouts.app')
@section('title','Edit Customer')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('sales.customers.index') }}">Customers</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'Edit Customer','subtitle'=>$customer->name])
<div class="row justify-content-center">
<div class="col-12 col-lg-7">
<div class="card">
<div class="card-header"><i class="fa fa-pen me-2 text-primary"></i>Edit Details</div>
<div class="card-body">
<form method="POST" action="{{ route('sales.customers.update',$customer) }}">
@csrf @method('PUT')
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Customer Code</label>
        <input type="text" class="form-control" value="{{ $customer->customer_code }}" readonly>
    </div>
    <div class="col-md-6">
        <label class="form-label">Type</label>
        <select name="customer_type" class="form-select">
            <option value="individual" {{ old('customer_type',$customer->customer_type)==='individual'?'selected':'' }}>Individual</option>
            <option value="business"   {{ old('customer_type',$customer->customer_type)==='business'?'selected':'' }}>Business</option>
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Full Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name',$customer->name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone <span class="text-danger">*</span></label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone',$customer->phone) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email',$customer->email) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">City</label>
        <input type="text" name="city" class="form-control" value="{{ old('city',$customer->city) }}">
    </div>
    <div class="col-12">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2">{{ old('address',$customer->address) }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes',$customer->notes) }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="active"   {{ old('status',$customer->status)==='active'?'selected':'' }}>Active</option>
            <option value="inactive" {{ old('status',$customer->status)==='inactive'?'selected':'' }}>Inactive</option>
        </select>
    </div>
</div>
<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary px-4"><i class="fa fa-save me-1"></i>Update</button>
    <a href="{{ route('sales.customers.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>
</form>
</div>
</div>
</div>
</div>
@endsection
