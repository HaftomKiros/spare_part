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
    <div class="col-md-6">
        <label class="form-label">Category <span class="text-danger">*</span></label>
        <select name="part_category_id" class="form-select" required>
            @foreach($categories as $cat)
                <optgroup label="{{ $cat->name }}">
                    <option value="{{ $cat->id }}" {{ old('part_category_id', $sparePart->part_category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @foreach($cat->children as $child)
                        <option value="{{ $child->id }}" {{ old('part_category_id', $sparePart->part_category_id) == $child->id ? 'selected' : '' }}>
                            &nbsp;&nbsp;↳ {{ $child->name }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
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
        <label class="form-label">Storage Location</label>
        <input type="text" name="location" class="form-control" value="{{ old('location', $sparePart->location) }}">
    </div>
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description', $sparePart->description) }}</textarea>
    </div>
</div>
</div>
</div>

<div class="card">
<div class="card-header"><i class="fa fa-motorcycle me-2 text-primary"></i>Compatible Vehicles</div>
<div class="card-body">
    <div class="row g-2" style="max-height:200px;overflow-y:auto">
        @foreach($vehicles as $v)
        <div class="col-6 col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="compatible_vehicles[]"
                       value="{{ $v->id }}" id="veh_{{ $v->id }}"
                       {{ $sparePart->compatibleVehicles->contains($v->id) ? 'checked' : '' }}>
                <label class="form-check-label small" for="veh_{{ $v->id }}">
                    {{ $v->brand }} {{ $v->model_name }}
                </label>
            </div>
        </div>
        @endforeach
    </div>
</div>
</div>
</div>

<div class="col-12 col-lg-4">
<div class="card mb-3">
<div class="card-header"><i class="fa fa-sack-dollar me-2 text-primary"></i>Pricing</div>
<div class="card-body">
    <div class="mb-3">
        <label class="form-label">Buying Price</label>
        <div class="input-group">
            <span class="input-group-text">Br</span>
            <input type="number" name="buying_price" id="buyPrice" class="form-control currency-input"
                   value="{{ old('buying_price', $sparePart->buying_price) }}" min="0" step="0.01" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Selling Price</label>
        <div class="input-group">
            <span class="input-group-text">Br</span>
            <input type="number" name="selling_price" id="sellPrice" class="form-control currency-input"
                   value="{{ old('selling_price', $sparePart->selling_price) }}" min="0" step="0.01" required>
        </div>
    </div>
    <div class="p-2 rounded bg-light text-center small">Margin: <strong id="marginDisplay">{{ $sparePart->profit_margin }}%</strong></div>
</div>
</div>

<div class="card mb-3">
<div class="card-header"><i class="fa fa-warehouse me-2 text-primary"></i>Stock</div>
<div class="card-body">
    <div class="mb-3">
        <label class="form-label">Current Stock</label>
        <input type="text" class="form-control" value="{{ $sparePart->current_stock }}" disabled>
        <div class="form-text">Use Inventory → Stock Entry to add stock.</div>
    </div>
    <div>
        <label class="form-label">Reorder Level</label>
        <input type="number" name="reorder_level" class="form-control" value="{{ old('reorder_level', $sparePart->reorder_level) }}" min="0" required>
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
@push('scripts')
<script>
function updateMargin() {
    const buy  = parseFloat(document.getElementById('buyPrice').value)  || 0;
    const sell = parseFloat(document.getElementById('sellPrice').value) || 0;
    const margin = buy > 0 ? (((sell - buy) / buy) * 100).toFixed(1) : 0;
    document.getElementById('marginDisplay').textContent = margin + '%';
}
document.getElementById('buyPrice')?.addEventListener('input', updateMargin);
document.getElementById('sellPrice')?.addEventListener('input', updateMargin);
</script>
@endpush
@endsection
