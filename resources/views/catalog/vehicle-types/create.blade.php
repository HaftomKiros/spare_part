@extends('layouts.app')
@section('title', 'Add Vehicle Type')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.vehicle-types.index') }}">Vehicle Types</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection

@section('content')
@include('partials.page-header', ['title' => 'Add Vehicle Type', 'subtitle' => 'Create a new vehicle category'])

<div class="row justify-content-center">
<div class="col-12 col-lg-7">
<div class="card">
<div class="card-header"><i class="fa fa-motorcycle me-2 text-primary"></i>Vehicle Type Details</div>
<div class="card-body">
<form method="POST" action="{{ route('catalog.vehicle-types.store') }}">
    @csrf

    <div class="mb-3">
        <label class="form-label">Type Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name') }}" placeholder="e.g. Two Wheeler" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Number of Wheels <span class="text-danger">*</span></label>
        <select name="wheel_count" class="form-select @error('wheel_count') is-invalid @enderror" required>
            <option value="">Select…</option>
            <option value="2" {{ old('wheel_count') == 2 ? 'selected' : '' }}>2 — Two Wheeler (Motorcycle / Scooter)</option>
            <option value="3" {{ old('wheel_count') == 3 ? 'selected' : '' }}>3 — Three Wheeler (Auto / Tricycle)</option>
        </select>
        @error('wheel_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3"
                  placeholder="Optional description…">{{ old('description') }}</textarea>
    </div>

    <div class="mb-4">
        <label class="form-label">Status <span class="text-danger">*</span></label>
        <select name="status" class="form-select" required>
            <option value="active"   {{ old('status', 'active') === 'active'   ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
            <i class="fa fa-save me-1"></i> Save Vehicle Type
        </button>
        <a href="{{ route('catalog.vehicle-types.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
    </div>
</form>
</div>
</div>
</div>
</div>
@endsection
