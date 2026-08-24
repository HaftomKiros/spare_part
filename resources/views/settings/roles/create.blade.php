@extends('layouts.app')
@section('title','Add Role')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('settings.roles.index') }}">Roles</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'Add Role'])
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
<div class="card">
<div class="card-header"><i class="fa fa-shield-halved me-2 text-primary"></i>Role Details</div>
<div class="card-body">
<form method="POST" action="{{ route('settings.roles.store') }}">
@csrf
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <label class="form-label">Role Slug <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name') }}" placeholder="e.g. cashier" required>
        <div class="form-text">Lowercase, no spaces</div>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Display Name <span class="text-danger">*</span></label>
        <input type="text" name="display_name" class="form-control @error('display_name') is-invalid @enderror"
               value="{{ old('display_name') }}" placeholder="e.g. Cashier" required>
        @error('display_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Description</label>
        <input type="text" name="description" class="form-control" value="{{ old('description') }}">
    </div>
</div>

<div class="mb-3"><div class="divider-label">Permissions</div></div>
<div class="row g-2">
    @foreach($permissions as $key => $label)
    <div class="col-6 col-md-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="permissions[]"
                   value="{{ $key }}" id="perm_{{ $loop->index }}"
                   {{ in_array($key, old('permissions', [])) ? 'checked' : '' }}>
            <label class="form-check-label small" for="perm_{{ $loop->index }}">{{ $label }}</label>
        </div>
    </div>
    @endforeach
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary px-4"><i class="fa fa-save me-1"></i>Save Role</button>
    <a href="{{ route('settings.roles.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>
</form>
</div>
</div>
</div>
</div>
@endsection
