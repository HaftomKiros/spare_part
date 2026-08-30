@extends('layouts.app')
@section('title', 'Add Spare Part')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.spare-parts.index') }}">Spare Parts</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection
@section('content')
@include('partials.page-header', ['title' => 'Add Spare Part', 'subtitle' => 'Register a new spare part in the catalog'])

<form id="partForm" method="POST" action="{{ route('catalog.spare-parts.store') }}">
@csrf
<div class="row g-3">

<!-- Main Info -->
<div class="col-12 col-lg-8">
<div class="card mb-3">
<div class="card-header"><i class="fa fa-gears me-2 text-primary"></i>Part Information</div>
<div class="card-body">
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Part Number <span class="text-danger">*</span></label>
        <input type="text" name="part_number" class="form-control @error('part_number') is-invalid @enderror"
               value="{{ old('part_number', $partNumber) }}" required>
        @error('part_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">OEM Number</label>
        <input type="text" name="oem_number" class="form-control" value="{{ old('oem_number') }}" placeholder="Original part number">
    </div>
    <div class="col-12">
        <label class="form-label">Part Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name') }}" placeholder="e.g. Piston Ring Set (Boxer)" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label">Vehicle Models</label>
        <select name="compatible_vehicles[]" id="compatible_vehicles" class="form-select" multiple>
            @foreach($vehicles as $v)
            <option value="{{ $v->id }}" {{ in_array($v->id, old('compatible_vehicles', [])) ? 'selected' : '' }}>
                {{ $v->brand }} {{ $v->model_name }}{{ $v->model_code ? ' ('.$v->model_code.')' : '' }} — {{ $v->vehicleType->name }}
            </option>
            @endforeach
        </select>
        <div class="form-text">Search by brand, model name or type. Select all that apply.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Unit of Measure <span class="text-danger">*</span></label>
        <select name="unit_id" class="form-select ts-select @error('unit_id') is-invalid @enderror" required>
            <option value="">Select unit…</option>
            @foreach($units as $u)
                <option value="{{ $u->id }}" {{ old('unit_id') == $u->id ? 'selected' : '' }}>
                    {{ $u->name }} ({{ $u->abbreviation }})
                </option>
            @endforeach
        </select>
        @error('unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Shelf</label>
        <input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="e.g. A-3, Shelf 2B">
    </div>
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
    </div>
</div>
</div>
</div>

</div>
</div>

<!-- Side Panel -->
<div class="col-12 col-lg-4">
<div class="card mb-3">
<div class="card-header"><i class="fa fa-warehouse me-2 text-primary"></i>Stock</div>
<div class="card-body">
    <div class="mb-3">
        <label class="form-label">Opening Stock <span class="text-danger">*</span></label>
        <input type="number" name="current_stock" class="form-control" value="{{ old('current_stock', 0) }}" min="0" required>
    </div>
    <div>
        <label class="form-label">Reorder Level <span class="text-danger">*</span></label>
        <input type="number" name="reorder_level" class="form-control" value="{{ old('reorder_level', 5) }}" min="0" required>
        <div class="form-text">Alert threshold</div>
    </div>
</div>
</div>

<div class="card mb-3">
<div class="card-header"><i class="fa fa-tag me-2 text-primary"></i>Pricing</div>
<div class="card-body">
    <div class="mb-3">
        <label class="form-label">Min Selling Price (Br) <span class="text-danger">*</span></label>
        <input type="number" name="selling_price_min" class="form-control @error('selling_price_min') is-invalid @enderror"
               value="{{ old('selling_price_min', 0) }}" min="0" step="0.01" required>
        <div class="form-text">Lowest price allowed when selling.</div>
        @error('selling_price_min')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div>
        <label class="form-label">Max Selling Price (Br) <span class="text-danger">*</span></label>
        <input type="number" name="selling_price_max" class="form-control @error('selling_price_max') is-invalid @enderror"
               value="{{ old('selling_price_max', 0) }}" min="0" step="0.01" required>
        <div class="form-text">Default price shown when selling.</div>
        @error('selling_price_max')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
</div>

<div class="card">
<div class="card-header"><i class="fa fa-toggle-on me-2 text-primary"></i>Status</div>
<div class="card-body">
    <select name="status" class="form-select mb-3">
        <option value="active">Active</option>
        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
    </select>
    <div class="d-grid">
        <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Save Spare Part</button>
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
