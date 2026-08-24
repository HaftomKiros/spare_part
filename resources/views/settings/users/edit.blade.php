@extends('layouts.app')
@section('title','Edit User')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('settings.users.index') }}">Users</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'Edit User','subtitle'=>$user->name])
<div class="row justify-content-center">
<div class="col-12 col-lg-7">
<div class="card">
<div class="card-header"><i class="fa fa-pen me-2 text-primary"></i>Edit Details</div>
<div class="card-body">
<form method="POST" action="{{ route('settings.users.update',$user) }}">
@csrf @method('PUT')
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Full Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name',$user->name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control" value="{{ old('email',$user->email) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone',$user->phone) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Role</label>
        <select name="role_id" class="form-select">
            <option value="">No Role</option>
            @foreach($roles as $r)
                <option value="{{ $r->id }}" {{ old('role_id',$user->role_id) == $r->id ? 'selected' : '' }}>{{ $r->display_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">New Password <span class="text-muted small">(leave blank to keep)</span></label>
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Confirm New Password</label>
        <input type="password" name="password_confirmation" class="form-control">
    </div>
    <div class="col-md-6">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="active"   {{ old('status',$user->status)==='active'?'selected':'' }}>Active</option>
            <option value="inactive" {{ old('status',$user->status)==='inactive'?'selected':'' }}>Inactive</option>
        </select>
    </div>
</div>
<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary px-4"><i class="fa fa-save me-1"></i>Update</button>
    <a href="{{ route('settings.users.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>
</form>
</div>
</div>
</div>
</div>
@endsection
