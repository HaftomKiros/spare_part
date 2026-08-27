@extends('layouts.app')
@section('title', 'Add Vehicle Model')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.vehicle-models.index') }}">Vehicle Models</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection
@section('content')
@include('partials.page-header', ['title' => 'Add Vehicle Model', 'subtitle' => 'Register a new vehicle model'])

{{-- Form wraps the entire row so all inputs are submitted --}}
<form method="POST" action="{{ route('catalog.vehicle-models.store') }}" id="modelForm">
@csrf
<div class="row g-3">

{{-- LEFT --}}
<div class="col-12 col-lg-8">
<div class="card mb-3">
<div class="card-header"><i class="fa fa-car me-2 text-primary"></i>Model Information</div>
<div class="card-body">
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Vehicle Type <span class="text-danger">*</span></label>
        <select name="vehicle_type_id" class="form-select ts-select @error('vehicle_type_id') is-invalid @enderror" required>
            <option value="">Select type…</option>
            @foreach($types as $t)
                <option value="{{ $t->id }}" {{ old('vehicle_type_id') == $t->id ? 'selected' : '' }}>
                    {{ $t->name }}
                </option>
            @endforeach
        </select>
        @error('vehicle_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Brand <span class="text-danger">*</span></label>
        <input type="text" name="brand" class="form-control" value="{{ old('brand', 'Bajaj') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Model Name <span class="text-danger">*</span></label>
        <input type="text" name="model_name" class="form-control @error('model_name') is-invalid @enderror"
               value="{{ old('model_name') }}" placeholder="e.g. Boxer, Pulsar 150" required>
        @error('model_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Model Code</label>
        <input type="text" name="model_code" class="form-control" value="{{ old('model_code') }}" placeholder="e.g. BX100">
    </div>
    <div class="col-md-6">
        <label class="form-label">Year</label>
        <input type="number" name="year" class="form-control" value="{{ old('year', date('Y')) }}" min="2000" max="{{ date('Y') + 1 }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Engine Displacement</label>
        <input type="text" name="engine_cc" class="form-control" value="{{ old('engine_cc') }}" placeholder="e.g. 150cc">
    </div>
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
    </div>
</div>
</div>
</div>
</div>{{-- /col-lg-8 --}}

{{-- RIGHT --}}
<div class="col-12 col-lg-4">

<div class="card mb-3">
<div class="card-header"><i class="fa fa-tag me-2 text-primary"></i>Pricing</div>
<div class="card-body">
    <div class="mb-3">
        <label class="form-label">Min Selling Price (Br)</label>
        <input type="number" name="selling_price_min" class="form-control @error('selling_price_min') is-invalid @enderror"
               value="{{ old('selling_price_min', 0) }}" min="0" step="0.01">
        <div class="form-text">Lowest allowed price when selling.</div>
        @error('selling_price_min')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div>
        <label class="form-label">Max Selling Price (Br)</label>
        <input type="number" name="selling_price_max" class="form-control @error('selling_price_max') is-invalid @enderror"
               value="{{ old('selling_price_max', 0) }}" min="0" step="0.01">
        <div class="form-text">Default price shown when selling.</div>
        @error('selling_price_max')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
</div>

<div class="card mb-3">
<div class="card-header"><i class="fa fa-warehouse me-2 text-primary"></i>Initial Stock</div>
<div class="card-body">
    <div class="mb-3">
        <label class="form-label">Opening Stock</label>
        <input type="number" name="opening_stock" class="form-control" value="{{ old('opening_stock', 0) }}" min="0">
    </div>
    <div>
        <label class="form-label">Reorder Level</label>
        <input type="number" name="reorder_level" class="form-control" value="{{ old('reorder_level', 2) }}" min="0">
        <div class="form-text">Alert when stock falls below this level.</div>
    </div>
</div>
</div>

<div class="card">
<div class="card-header"><i class="fa fa-toggle-on me-2 text-primary"></i>Status</div>
<div class="card-body">
    <select name="status" class="form-select mb-3">
        <option value="active"   {{ old('status', 'active') === 'active'   ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
    </select>
    <button type="submit" class="btn btn-primary w-100">
        <i class="fa fa-save me-1"></i>Save Model
    </button>
    <a href="{{ route('catalog.vehicle-models.index') }}" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
</div>
</div>

</div>{{-- /col-lg-4 --}}
</div>{{-- /row --}}
</form>
@endsection
