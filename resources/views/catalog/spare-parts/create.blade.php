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
    <div class="col-md-6">
        <label class="form-label">Category <span class="text-danger">*</span></label>
        <select name="part_category_id" class="form-select @error('part_category_id') is-invalid @enderror" required>
            <option value="">Select category…</option>
            @foreach($categories as $cat)
                <optgroup label="{{ $cat->name }}">
                    <option value="{{ $cat->id }}" {{ old('part_category_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                    @foreach($cat->children as $child)
                        <option value="{{ $child->id }}" {{ old('part_category_id') == $child->id ? 'selected' : '' }}>
                            &nbsp;&nbsp;↳ {{ $child->name }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @error('part_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Unit of Measure <span class="text-danger">*</span></label>
        <select name="unit_id" class="form-select @error('unit_id') is-invalid @enderror" required>
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
        <label class="form-label">Storage Location</label>
        <input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="e.g. Shelf A-3">
    </div>
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
    </div>
</div>
</div>
</div>

<!-- Compatible Vehicles -->
<div class="card">
<div class="card-header"><i class="fa fa-motorcycle me-2 text-primary"></i>Compatible Vehicles</div>
<div class="card-body">
    <div class="row g-2" style="max-height:200px;overflow-y:auto">
        @foreach($vehicles as $v)
        <div class="col-6 col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="compatible_vehicles[]"
                       value="{{ $v->id }}" id="veh_{{ $v->id }}"
                       {{ in_array($v->id, old('compatible_vehicles', [])) ? 'checked' : '' }}>
                <label class="form-check-label small" for="veh_{{ $v->id }}">
                    {{ $v->brand }} {{ $v->model_name }}
                    <span class="text-muted">({{ $v->vehicleType->name }})</span>
                </label>
            </div>
        </div>
        @endforeach
    </div>
</div>
</div>
</div>

<!-- Side Panel -->
<div class="col-12 col-lg-4">
<div class="card mb-3">
<div class="card-header"><i class="fa fa-sack-dollar me-2 text-primary"></i>Pricing</div>
<div class="card-body">
    <div class="mb-3">
        <label class="form-label">Buying Price (Br) <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">Br</span>
            <input type="number" name="buying_price" id="buyPrice" class="form-control currency-input"
                   value="{{ old('buying_price', '0.00') }}" min="0" step="0.01" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Selling Price (Br) <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">Br</span>
            <input type="number" name="selling_price" id="sellPrice" class="form-control currency-input"
                   value="{{ old('selling_price', '0.00') }}" min="0" step="0.01" required>
        </div>
    </div>
    <div class="p-2 rounded bg-light text-center small">
        Margin: <strong id="marginDisplay">0%</strong>
    </div>
</div>
</div>

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

@push('scripts')
<script>
function updateMargin() {
    const buy  = parseFloat(document.getElementById('buyPrice').value)  || 0;
    const sell = parseFloat(document.getElementById('sellPrice').value) || 0;
    const margin = buy > 0 ? (((sell - buy) / buy) * 100).toFixed(1) : 0;
    document.getElementById('marginDisplay').textContent = margin + '%';
    document.getElementById('marginDisplay').style.color = margin >= 0 ? '#16a34a' : '#dc2626';
}
document.getElementById('buyPrice')?.addEventListener('input', updateMargin);
document.getElementById('sellPrice')?.addEventListener('input', updateMargin);
updateMargin();
</script>
@endpush
@endsection
