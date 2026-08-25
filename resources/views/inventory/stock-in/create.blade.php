@extends('layouts.app')
@section('title', 'New Stock Entry')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('inventory.stock-in.index') }}">Stock Entry</a></li>
    <li class="breadcrumb-item active">New Entry</li>
@endsection
@section('content')
@include('partials.page-header', ['title' => 'New Stock Entry', 'subtitle' => 'Add stock to a specific warehouse'])

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

    <!-- Warehouse Selector -->
    <div class="mb-3">
        <label class="form-label">Warehouse <span class="text-danger">*</span></label>
        <select name="warehouse_id" class="form-select ts-select @error('warehouse_id') is-invalid @enderror">
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}"
                    {{ old('warehouse_id', $defaultWarehouse?->id) == $wh->id ? 'selected' : '' }}>
                    {{ $wh->name }}{{ $wh->is_default ? ' (Default)' : '' }}
                </option>
            @endforeach
        </select>
        @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <!-- Vehicle Selector -->
    <div id="vehicleSection" class="mb-3" style="display:none">
        <label class="form-label">Select Vehicle Model</label>
        <select name="item_id" id="vehicleSelect" class="form-select ts-select">
            <option value="">- Choose vehicle -</option>
            @foreach($vehicleTypes as $vt)
                <optgroup label="{{ $vt->name }} ({{ $vt->wheel_count }}-Wheeler)">
                    @foreach($vt->activeVehicleModels as $vm)
                        <option value="{{ $vm->id }}"
                                data-stock="{{ $vm->stock?->current_stock ?? 0 }}"
                                {{ old('item_type') === 'vehicle' && old('item_id') == $vm->id ? 'selected' : '' }}>
                            {{ $vm->brand }} {{ $vm->model_name }}
                            {{ $vm->model_code ? '('.$vm->model_code.')' : '' }}
                            - Stock: {{ $vm->stock?->current_stock ?? 0 }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>

    <!-- Spare Part Selector -->
    <div id="partSection" class="mb-3">
        <label class="form-label">Select Spare Part</label>
        <select name="item_id" id="partSelect" class="form-select ts-select">
            <option value="">- Choose spare part -</option>
            @foreach($categories as $cat)
                <optgroup label="{{ $cat->name }}">
                    @foreach($cat->spareParts as $part)
                        <option value="{{ $part->id }}"
                                data-stock="{{ $part->current_stock }}"
                                {{ old('item_type', 'spare_part') === 'spare_part' && old('item_id') == $part->id ? 'selected' : '' }}>
                            {{ $part->name }} ({{ $part->part_number }}) - Stock: {{ $part->current_stock }} {{ $part->unit->abbreviation }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>

    <!-- Current Stock Preview -->
    <div id="currentStockPreview" class="alert alert-info py-2 small mb-3" style="display:none">
        <i class="fa fa-info-circle me-1"></i>
        Stock in <strong id="warehouseLabel">selected warehouse</strong>:
        <strong id="currentStockValue">0</strong>
        <span class="text-muted ms-2" id="globalStockNote"></span>
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
        <div class="fs-4 fw-bold text-success" id="newStockValue">-</div>
    </div>

    <div class="mb-4">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2"
                  placeholder="Reason for adding stock, reference number, etc.">{{ old('notes') }}</textarea>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success px-4">
            <i class="fa fa-arrow-down-to-bracket me-1"></i> Save Entry
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
const warehouseSel = document.querySelector('[name="warehouse_id"]');

let currentWarehouseStock = 0;

function toggleSections() {
    const isVehicle = vehicleRadio.checked;
    vehicleSec.style.display = isVehicle ? '' : 'none';
    partSec.style.display    = isVehicle ? 'none' : '';
    // Handle both plain select and TomSelect
    if (vehicleSel._tomSelect && partSel._tomSelect) {
        if (isVehicle) { partSel._tomSelect.disable(); vehicleSel._tomSelect.enable(); }
        else           { vehicleSel._tomSelect.disable(); partSel._tomSelect.enable(); }
    } else {
        vehicleSel.disabled = !isVehicle;
        partSel.disabled    = isVehicle;
    }
    fetchWarehouseStock();
}

function fetchWarehouseStock() {
    const sel         = vehicleRadio.checked ? vehicleSel : partSel;
    const itemId      = sel.value;
    const itemType    = vehicleRadio.checked ? 'vehicle' : 'spare_part';
    const warehouseId = warehouseSel?.value;

    if (!itemId) {
        preview.style.display    = 'none';
        newPreview.style.display = 'none';
        return;
    }

    // Show loading state
    document.getElementById('currentStockValue').textContent = '...';
    preview.style.display = '';

    fetch(`{{ route('inventory.stock-in.warehouse-stock') }}?warehouse_id=${warehouseId}&item_type=${itemType}&item_id=${itemId}`)
        .then(r => r.json())
        .then(data => {
            currentWarehouseStock = data.stock;
            const whName = warehouseSel?.options[warehouseSel.selectedIndex]?.text?.replace(' (Default)', '') || 'warehouse';
            document.getElementById('warehouseLabel').textContent = whName;
            document.getElementById('currentStockValue').textContent = data.stock;
            preview.style.display = '';
            updateNewStock();
        })
        .catch(() => {
            // Fallback to data-stock attribute if AJAX fails
            const opt = sel.options[sel.selectedIndex];
            currentWarehouseStock = parseInt(opt?.dataset.stock ?? 0);
            document.getElementById('currentStockValue').textContent = currentWarehouseStock;
            updateNewStock();
        });
}

function updateNewStock() {
    const sel = vehicleRadio.checked ? vehicleSel : partSel;
    if (!sel.value) {
        newPreview.style.display = 'none';
        return;
    }
    const qty = parseInt(qtyInput.value || 0);
    document.getElementById('newStockValue').textContent = currentWarehouseStock + qty;
    newPreview.style.display = '';
}

vehicleRadio.addEventListener('change', toggleSections);
partRadio.addEventListener('change', toggleSections);
vehicleSel.addEventListener('change', fetchWarehouseStock);
partSel.addEventListener('change', fetchWarehouseStock);
qtyInput.addEventListener('input', updateNewStock);
if (warehouseSel) warehouseSel.addEventListener('change', fetchWarehouseStock);

// Also hook TomSelect onChange once instances are created (global init runs after)
document.addEventListener('DOMContentLoaded', function () {
    if (vehicleSel._tomSelect) vehicleSel._tomSelect.on('change', fetchWarehouseStock);
    if (partSel._tomSelect)    partSel._tomSelect.on('change', fetchWarehouseStock);
    if (warehouseSel?._tomSelect) warehouseSel._tomSelect.on('change', fetchWarehouseStock);
});

toggleSections(); // initial
</script>
@endpush
@endsection
