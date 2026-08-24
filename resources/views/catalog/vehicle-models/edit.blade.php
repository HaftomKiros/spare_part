@extends('layouts.app')
@section('title', 'Edit Vehicle Model')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.vehicle-models.index') }}">Vehicle Models</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
@include('partials.page-header', ['title' => 'Edit Vehicle Model', 'subtitle' => $vehicleModel->full_name])

<form id="modelForm" method="POST" action="{{ route('catalog.vehicle-models.update', $vehicleModel) }}">
@csrf @method('PUT')
<div class="row g-3">
<div class="col-12 col-lg-8">
<div class="card">
<div class="card-header"><i class="fa fa-pen me-2 text-primary"></i>Model Information</div>
<div class="card-body">
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Vehicle Type <span class="text-danger">*</span></label>
        <select name="vehicle_type_id" class="form-select" required>
            @foreach($types as $t)
                <option value="{{ $t->id }}" {{ old('vehicle_type_id', $vehicleModel->vehicle_type_id) == $t->id ? 'selected' : '' }}>
                    {{ $t->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Brand <span class="text-danger">*</span></label>
        <input type="text" name="brand" class="form-control" value="{{ old('brand', $vehicleModel->brand) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Model Name <span class="text-danger">*</span></label>
        <input type="text" name="model_name" class="form-control" value="{{ old('model_name', $vehicleModel->model_name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Model Code</label>
        <input type="text" name="model_code" class="form-control" value="{{ old('model_code', $vehicleModel->model_code) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Year</label>
        <input type="number" name="year" class="form-control" value="{{ old('year', $vehicleModel->year) }}" min="2000" max="{{ date('Y')+1 }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Engine CC</label>
        <input type="text" name="engine_cc" class="form-control" value="{{ old('engine_cc', $vehicleModel->engine_cc) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Buying Price (Br) <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">Br</span>
            <input type="number" name="buying_price" class="form-control currency-input"
                   value="{{ old('buying_price', $vehicleModel->buying_price) }}" min="0" step="0.01" required>
        </div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Selling Price (Br) <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">Br</span>
            <input type="number" name="selling_price" class="form-control currency-input"
                   value="{{ old('selling_price', $vehicleModel->selling_price) }}" min="0" step="0.01" required>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description', $vehicleModel->description) }}</textarea>
    </div>
</div>
</div>
</div>
</div>

<div class="col-12 col-lg-4">
    <div class="card mb-3">
    <div class="card-header"><i class="fa fa-warehouse me-2 text-primary"></i>Stock Settings</div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Current Stock</label>
            <input type="text" class="form-control" value="{{ $vehicleModel->stock?->current_stock ?? 0 }}" disabled readonly>
            <div class="form-text">Use Inventory → Stock In to add stock.</div>
        </div>
        <div>
            <label class="form-label">Reorder Level</label>
            <input type="number" name="reorder_level" class="form-control" value="{{ old('reorder_level', $vehicleModel->stock?->reorder_level ?? 2) }}" min="0">
        </div>
    </div>
    </div>
    <div class="card">
    <div class="card-header"><i class="fa fa-toggle-on me-2 text-primary"></i>Status</div>
    <div class="card-body">
        <select name="status" class="form-select mb-3">
            <option value="active"   {{ old('status', $vehicleModel->status) === 'active'   ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $vehicleModel->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Update Model</button>
        </div>
        <a href="{{ route('catalog.vehicle-models.index') }}" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
    </div>
    </div>
</div>
</div>
</form>
@endsection
