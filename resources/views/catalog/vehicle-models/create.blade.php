@extends('layouts.app')
@section('title', 'Add Vehicle Model')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.vehicle-models.index') }}">Vehicle Models</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection

@section('content')
@include('partials.page-header', ['title' => 'Add Vehicle Model', 'subtitle' => 'Register a new Bajaj vehicle model'])

<div class="row g-3">
<div class="col-12 col-lg-8">
<div class="card">
<div class="card-header"><i class="fa fa-car me-2 text-primary"></i>Model Information</div>
<div class="card-body">
<form method="POST" action="{{ route('catalog.vehicle-models.store') }}">
@csrf
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Vehicle Type <span class="text-danger">*</span></label>
        <select name="vehicle_type_id" class="form-select ts-select @error('vehicle_type_id') is-invalid @enderror" required>
            <option value="">Select type…</option>
            @foreach($types as $t)
                <option value="{{ $t->id }}" {{ old('vehicle_type_id') == $t->id ? 'selected' : '' }}>
                    {{ $t->name }} ({{ $t->wheel_count }}-Wheeler)
                </option>
            @endforeach
        </select>
        @error('vehicle_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Brand <span class="text-danger">*</span></label>
        <input type="text" name="brand" class="form-control" value="{{ old('brand', 'Bajaj') }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Model Name <span class="text-danger">*</span></label>
        <input type="text" name="model_name" class="form-control @error('model_name') is-invalid @enderror"
               value="{{ old('model_name') }}" placeholder="e.g. Boxer, Pulsar 150, RE" required>
        @error('model_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Model Code</label>
        <input type="text" name="model_code" class="form-control" value="{{ old('model_code') }}" placeholder="e.g. BX100">
    </div>

    <div class="col-md-6">
        <label class="form-label">Year</label>
        <input type="number" name="year" class="form-control" value="{{ old('year', date('Y')) }}"
               min="2000" max="{{ date('Y') + 1 }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">Engine Displacement</label>
        <input type="text" name="engine_cc" class="form-control" value="{{ old('engine_cc') }}" placeholder="e.g. 150cc">
    </div>

    <div class="col-md-6">
        <label class="form-label">Buying Price (Br) <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">Br</span>
            <input type="number" name="buying_price" class="form-control currency-input @error('buying_price') is-invalid @enderror"
                   value="{{ old('buying_price', '0.00') }}" min="0" step="0.01" required>
        </div>
        @error('buying_price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Selling Price (Br) <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">Br</span>
            <input type="number" name="selling_price" class="form-control currency-input @error('selling_price') is-invalid @enderror"
                   value="{{ old('selling_price', '0.00') }}" min="0" step="0.01" required>
        </div>
        @error('selling_price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
    </div>
</div>
</div>
</div>
</div>

<!-- Side panel -->
<div class="col-12 col-lg-4">
<div class="card mb-3">
<div class="card-header"><i class="fa fa-warehouse me-2 text-primary"></i>Initial Stock</div>
<div class="card-body">
    <div class="mb-3">
        <label class="form-label">Opening Stock Quantity</label>
        <input type="number" name="opening_stock" class="form-control" value="{{ old('opening_stock', 0) }}" min="0">
    </div>
    <div class="mb-3">
        <label class="form-label">Reorder Level</label>
        <input type="number" name="reorder_level" class="form-control" value="{{ old('reorder_level', 2) }}" min="0">
        <div class="form-text">Alert when stock falls below this level.</div>
    </div>
</div>
</div>

<div class="card">
<div class="card-header"><i class="fa fa-toggle-on me-2 text-primary"></i>Status</div>
<div class="card-body">
    <select name="status" class="form-select">
        <option value="active"   {{ old('status', 'active') === 'active'   ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
    </select>
    <div class="d-grid mt-3">
        <button type="submit" form="modelForm" class="btn btn-primary">
            <i class="fa fa-save me-1"></i> Save Model
        </button>
    </div>
    <a href="{{ route('catalog.vehicle-models.index') }}" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
</div>
</div>
</div>
</div>

{{-- Attach form id so submit button outside form works --}}
<script>document.querySelector('form').id='modelForm';</script>
</form>
@endsection
