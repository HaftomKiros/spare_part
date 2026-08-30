@extends('layouts.app')
@section('title', 'Edit Spare Part')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.spare-parts.index') }}">Spare Parts</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection
@section('content')
@include('partials.page-header', ['title' => 'Edit Spare Part', 'subtitle' => $sparePart->name])

<form id="partForm" method="POST" action="{{ route('catalog.spare-parts.update', $sparePart) }}">
@csrf @method('PUT')
<div class="row g-3">

<div class="col-12 col-lg-8">
<div class="card mb-3">
<div class="card-header"><i class="fa fa-pen me-2 text-primary"></i>Part Information</div>
<div class="card-body">
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Part Number <span class="text-danger">*</span></label>
        <input type="text" name="part_number" class="form-control" value="{{ old('part_number', $sparePart->part_number) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">OEM Number</label>
        <input type="text" name="oem_number" class="form-control" value="{{ old('oem_number', $sparePart->oem_number) }}">
    </div>
    <div class="col-12">
        <label class="form-label">Part Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $sparePart->name) }}" required>
    </div>
    <div class="col-12">
        <label class="form-label">Vehicle Models</label>
        <select name="compatible_vehicles[]" id="compatible_vehicles" class="form-select" multiple>
            @foreach($vehicles as $v)
            <option value="{{ $v->id }}" {{ $sparePart->compatibleVehicles->contains($v->id) || in_array($v->id, old('compatible_vehicles', [])) ? 'selected' : '' }}>
                {{ $v->brand }} {{ $v->model_name }}{{ $v->model_code ? ' ('.$v->model_code.')' : '' }} — {{ $v->vehicleType->name }}
            </option>
            @endforeach
        </select>
        <div class="form-text">Search by brand, model name or type. Select all that apply.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Unit of Measure</label>
        <select name="unit_id" class="form-select" required>
            @foreach($units as $u)
                <option value="{{ $u->id }}" {{ old('unit_id', $sparePart->unit_id) == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->abbreviation }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Shelf</label>
        <input type="text" name="location" class="form-control" value="{{ old('location', $sparePart->location) }}" placeholder="e.g. A-3, Shelf 2B">
    </div>
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description', $sparePart->description) }}</textarea>
    </div>
</div>
</div>
</div>

</div>
</div>

<div class="col-12 col-lg-4">
<div class="card mb-3">
<div class="card-header"><i class="fa fa-warehouse me-2 text-primary"></i>Stock</div>
<div class="card-body">
    <div class="mb-3">
        <label class="form-label">Current Stock (Unsold)</label>
        <input type="text" class="form-control" value="{{ $unsoldStock }}" disabled>
        <div class="form-text">Total unsold across all purchase batches. Use Inventory → Stock Entry to add stock.</div>
    </div>
    <div>
        <label class="form-label">Reorder Level</label>
        <input type="number" name="reorder_level" class="form-control" value="{{ old('reorder_level', $sparePart->reorder_level) }}" min="0" required>
    </div>
</div>
</div>

<div class="card mb-3">
<div class="card-header"><i class="fa fa-tag me-2 text-primary"></i>Pricing</div>
<div class="card-body">
    <div class="mb-3">
        <label class="form-label">Min Selling Price (Br)</label>
        <input type="number" name="selling_price_min" class="form-control"
               value="{{ old('selling_price_min', $sparePart->selling_price_min) }}" min="0" step="0.01">
        <div class="form-text">Lowest price allowed when selling.</div>
    </div>
    <div>
        <label class="form-label">Max Selling Price (Br)</label>
        <input type="number" name="selling_price_max" class="form-control"
               value="{{ old('selling_price_max', $sparePart->selling_price_max) }}" min="0" step="0.01">
        <div class="form-text">Default price shown when selling.</div>
    </div>
</div>
</div>

<div class="card">
<div class="card-header"><i class="fa fa-toggle-on me-2 text-primary"></i>Status</div>
<div class="card-body">
    <select name="status" class="form-select mb-3">
        <option value="active"   {{ old('status', $sparePart->status) === 'active'   ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ old('status', $sparePart->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
    </select>
    <div class="d-grid">
        <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Update Part</button>
    </div>
    <a href="{{ route('catalog.spare-parts.index') }}" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
</div>
</div>
</div>
</div>
</form>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('compatible_vehicles');
    if (el && !el._tomSelect) {
        new TomSelect(el, {
            plugins: ['remove_button'],
            placeholder: 'Search by brand, model name or type…',
            maxOptions: 500,
        });
    }
});
</script>
@endpush
