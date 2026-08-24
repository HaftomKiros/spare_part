@extends('layouts.app')
@section('title', 'Edit Vehicle Type')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.vehicle-types.index') }}">Vehicle Types</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
@include('partials.page-header', ['title' => 'Edit Vehicle Type', 'subtitle' => $vehicleType->name])

<div class="row justify-content-center">
<div class="col-12 col-lg-7">
<div class="card">
<div class="card-header"><i class="fa fa-pen me-2 text-primary"></i>Edit Details</div>
<div class="card-body">
<form method="POST" action="{{ route('catalog.vehicle-types.update', $vehicleType) }}">
    @csrf @method('PUT')

    <div class="mb-3">
        <label class="form-label">Type Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $vehicleType->name) }}" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Number of Wheels <span class="text-danger">*</span></label>
        <select name="wheel_count" class="form-select" required>
            <option value="2" {{ old('wheel_count', $vehicleType->wheel_count) == 2 ? 'selected' : '' }}>2 — Two Wheeler</option>
            <option value="3" {{ old('wheel_count', $vehicleType->wheel_count) == 3 ? 'selected' : '' }}>3 — Three Wheeler</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description', $vehicleType->description) }}</textarea>
    </div>

    <div class="mb-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" required>
            <option value="active"   {{ old('status', $vehicleType->status) === 'active'   ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $vehicleType->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
            <i class="fa fa-save me-1"></i> Update
        </button>
        <a href="{{ route('catalog.vehicle-types.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
    </div>
</form>
</div>
</div>
</div>
</div>
@endsection
