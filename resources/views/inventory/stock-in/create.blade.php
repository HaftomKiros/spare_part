@extends('layouts.app')
@section('title', 'Add Stock')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('inventory.stock-in.index') }}">Stock In</a></li>
    <li class="breadcrumb-item active">Add Stock</li>
@endsection
@section('content')
@include('partials.page-header', ['title' => 'Add Stock', 'subtitle' => 'Manually add stock to a vehicle or spare part'])

<div class="row justify-content-center">
<div class="col-12 col-lg-7">
<div class="card">
<div class="card-header"><i class="fa fa-arrow-down-to-bracket me-2 text-success"></i>Stock Entry</div>
<div class="card-body">
<form method="POST" action="{{ route('inventory.stock-in.store') }}">
@csrf

    <!-- Item Type Toggle -->
    <div class="mb-4">
        <label class="form-label">Item Type <span class="text-danger">*</span></label>
        <div class="d-flex gap-3">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="item_type" id="typeVehicle" value="vehicle"
                       {{ old('item_type', 'spare_part') === 'vehicle' ? 'checked' : '' }}>
                <label class="form-check-label" for="typeVehicle">
                    <i class="fa fa-motorcycle me-1 text-primary"></i>Vehicle
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="item_type" id="typePart" value="spare_part"
                       {{ old('item_type', 'spare_part') === 'spare_part' ? 'checked' : '' }}>
                <label class="form-check-label" for="typePart">
                    <i class="fa fa-gears me-1 text-success"></i>Spare Part
                </label>
            </div>
        </div>
    </div>

    <!-- Vehicle Selector -->
    <div id="vehicleSection" class="mb-3" style="display:none">
        <label class="form-label">Select Vehicle Model</label>
        <select name="item_id" id="vehicleSelect" class="form-select">
            <option value="">— Choose vehicle —</option>
            @foreach($vehicleTypes as $vt)
                <optgroup label="{{ $vt->name }} ({{ $vt->wheel_count }}-Wheeler)">
                    @foreach($vt->activeVehicleModels as $vm)
                        <option value="{{ $vm->id }}"
                                data-stock="{{ $vm->stock?->current_stock ?? 0 }}"
                                {{ old('item_type') === 'vehicle' && old('item_id') == $vm->id ? 'selected' : '' }}>
                            {{ $vm->brand }} {{ $vm->model_name }}
                            {{ $vm->model_code ? '('.$vm->model_code.')' : '' }}
                            — Stock: {{ $vm->stock?->current_stock ?? 0 }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>

    <!-- Spare Part Selector -->
    <div id="partSection" class="mb-3">
        <label class="form-label">Select Spare Part</label>
        <select name="item_id" id="partSelect" class="form-select">
            <option value="">— Choose spare part —</option>
            @foreach($categories as $cat)
                <optgroup label="{{ $cat->name }}">
                    @foreach($cat->spareParts as $part)
                        <option value="{{ $part->id }}"
                                data-stock="{{ $part->current_stock }}"
                                {{ old('item_type', 'spare_part') === 'spare_part' && old('item_id') == $part->id ? 'selected' : '' }}>
                            {{ $part->name }} ({{ $part->part_number }}) — Stock: {{ $part->current_stock }} {{ $part->unit->abbreviation }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>

    <!-- Current Stock Preview -->
    <div id="currentStockPreview" class="alert alert-info py-2 small mb-3" style="display:none">
        <i class="fa fa-info-circle me-1"></i>
        Current stock: <strong id="currentStockValue">0</strong>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">Quantity to Add <span class="text-danger">*</span></label>
            <input type="number" name="quantity" id="qtyInput" class="form-control @error('quantity') is-invalid @enderror"
                   value="{{ old('quantity', 1) }}" min="1" required>
            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Unit Cost (Br)</label>
            <div class="input-group">
                <span class="input-group-text">Br</span>
                <input type="number" name="unit_cost" class="form-control currency-input"
                       value="{{ old('unit_cost', '0.00') }}" min="0" step="0.01">
            </div>
        </div>
    </div>

    <!-- New Stock Preview -->
    <div id="newStockPreview" class="mb-3 p-3 rounded-3 bg-success bg-opacity-10 text-center" style="display:none">
        <div class="small text-muted">New stock after addition</div>
        <div class="fs-4 fw-bold text-success" id="newStockValue">—</div>
    </div>

    <div class="mb-4">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2"
                  placeholder="Reason for adding stock, reference number, etc.">{{ old('notes') }}</textarea>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success px-4">
            <i class="fa fa-arrow-down-to-bracket me-1"></i> Add Stock
        </button>
        <a href="{{ route('inventory.stock-in.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
    </div>
</form>
</div>
</div>
</div>
</div>

@push('scripts')
<script>
const vehicleRadio = document.getElementById('typeVehicle');
const partRadio    = document.getElementById('typePart');
const vehicleSec   = document.getElementById('vehicleSection');
const partSec      = document.getElementById('partSection');
const vehicleSel   = document.getElementById('vehicleSelect');
const partSel      = document.getElementById('partSelect');
const qtyInput     = document.getElementById('qtyInput');
const preview      = document.getElementById('currentStockPreview');
const newPreview   = document.getElementById('newStockPreview');

function toggleSections() {
    const isVehicle = vehicleRadio.checked;
    vehicleSec.style.display = isVehicle ? '' : 'none';
    partSec.style.display    = isVehicle ? 'none' : '';
    // Disable inactive select so it's not submitted
    vehicleSel.disabled = !isVehicle;
    partSel.disabled    = isVehicle;
    updatePreview();
}

function updatePreview() {
    const sel     = vehicleRadio.checked ? vehicleSel : partSel;
    const opt     = sel.options[sel.selectedIndex];
    const current = parseInt(opt?.dataset.stock ?? 0);
    const qty     = parseInt(qtyInput.value || 0);

    if (sel.value) {
        document.getElementById('currentStockValue').textContent = current;
        preview.style.display = '';
        document.getElementById('newStockValue').textContent = current + qty;
        newPreview.style.display = '';
    } else {
        preview.style.display    = 'none';
        newPreview.style.display = 'none';
    }
}

vehicleRadio.addEventListener('change', toggleSections);
partRadio.addEventListener('change', toggleSections);
vehicleSel.addEventListener('change', updatePreview);
partSel.addEventListener('change', updatePreview);
qtyInput.addEventListener('input', updatePreview);

toggleSections(); // initial
</script>
@endpush
@endsection
