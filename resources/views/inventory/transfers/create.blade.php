@extends('layouts.app')
@section('title', 'New Stock Transfer')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('inventory.transfers.index') }}">Stock Transfer</a></li>
    <li class="breadcrumb-item active">New Transfer</li>
@endsection
@section('content')
@include('partials.page-header', ['title' => 'New Stock Transfer', 'subtitle' => 'Move stock between warehouses'])

<div class="row justify-content-center">
<div class="col-12 col-lg-8">

@if(session('error'))
<div class="alert alert-danger"><i class="fa fa-circle-xmark me-2"></i>{{ session('error') }}</div>
@endif

<div class="card">
<div class="card-header"><i class="fa fa-right-left me-2 text-warning"></i>Transfer Details</div>
<div class="card-body">
<form method="POST" action="{{ route('inventory.transfers.store') }}">
@csrf

<div class="row g-3 mb-4">
    <!-- From Warehouse -->
    <div class="col-md-5">
        <label class="form-label fw-semibold">From Warehouse <span class="text-danger">*</span></label>
        <select name="from_warehouse_id" id="fromWarehouse" class="form-select ts-select @error('from_warehouse_id') is-invalid @enderror" required>
            <option value="">Select source...</option>
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}" {{ old('from_warehouse_id') == $wh->id ? 'selected' : '' }}>
                    {{ $wh->name }}{{ $wh->is_default ? ' (Default)' : '' }}
                </option>
            @endforeach
        </select>
        @error('from_warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <!-- Arrow -->
    <div class="col-md-2 d-flex align-items-end justify-content-center pb-2">
        <i class="fa fa-arrow-right fa-xl text-warning"></i>
    </div>

    <!-- To Warehouse -->
    <div class="col-md-5">
        <label class="form-label fw-semibold">To Warehouse <span class="text-danger">*</span></label>
        <select name="to_warehouse_id" id="toWarehouse" class="form-select ts-select @error('to_warehouse_id') is-invalid @enderror" required>
            <option value="">Select destination...</option>
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}" {{ old('to_warehouse_id') == $wh->id ? 'selected' : '' }}>
                    {{ $wh->name }}{{ $wh->is_default ? ' (Default)' : '' }}
                </option>
            @endforeach
        </select>
        @error('to_warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

{{-- Same warehouse warning --}}
<div id="sameWarehouseAlert" class="alert alert-danger py-2 small d-none">
    <i class="fa fa-circle-xmark me-1"></i>
    Source and destination warehouse cannot be the same. Please select a different warehouse.
</div>

<hr class="my-3">

<!-- Item Type -->
<div class="mb-3">
    <label class="form-label fw-semibold">Item Type <span class="text-danger">*</span></label>
    <div class="d-flex gap-3">
        <div class="form-check">
            <input class="form-check-input" type="radio" name="item_type" id="typePart" value="spare_part"
                   {{ old('item_type', 'spare_part') === 'spare_part' ? 'checked' : '' }}>
            <label class="form-check-label" for="typePart">
                <i class="fa fa-gears me-1 text-success"></i>Spare Part
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="item_type" id="typeVehicle" value="vehicle"
                   {{ old('item_type') === 'vehicle' ? 'checked' : '' }}>
            <label class="form-check-label" for="typeVehicle">
                <i class="fa fa-motorcycle me-1 text-primary"></i>Vehicle
            </label>
        </div>
    </div>
</div>

<!-- Item selector -->
<div class="mb-3" id="partSection">
    <label class="form-label fw-semibold">Spare Part <span class="text-danger">*</span></label>
    <select name="item_id" id="partSelect" class="form-select ts-select @error('item_id') is-invalid @enderror">
        <option value="">- Choose spare part -</option>
        @foreach($parts as $p)
            <option value="{{ $p->id }}" {{ old('item_id') == $p->id ? 'selected' : '' }}>
                {{ $p->name }} ({{ $p->part_number }}) - {{ $p->unit->abbreviation }}
            </option>
        @endforeach
    </select>
    @error('item_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3" id="vehicleSection" style="display:none">
    <label class="form-label fw-semibold">Vehicle Model <span class="text-danger">*</span></label>
    <select name="item_id" id="vehicleSelect" class="form-select ts-select" disabled>
        <option value="">- Choose vehicle -</option>
        @foreach($vehicles as $v)
            <option value="{{ $v->id }}" {{ old('item_id') == $v->id ? 'selected' : '' }}>
                {{ $v->brand }} {{ $v->model_name }}{{ $v->model_code ? ' ('.$v->model_code.')' : '' }}
                - {{ $v->vehicleType->name }}
            </option>
        @endforeach
    </select>
</div>

<!-- Stock info boxes -->
<div class="row g-3 mb-3" id="stockInfo" style="display:none">
    <div class="col-6">
        <div class="p-3 rounded-3 border text-center">
            <div class="small text-muted mb-1">Available in Source</div>
            <div class="fs-4 fw-bold text-primary" id="fromStock">-</div>
            <div class="small text-muted" id="fromWarehouseName">-</div>
        </div>
    </div>
    <div class="col-6">
        <div class="p-3 rounded-3 border text-center">
            <div class="small text-muted mb-1">Current in Destination</div>
            <div class="fs-4 fw-bold text-success" id="toStock">-</div>
            <div class="small text-muted" id="toWarehouseName">-</div>
        </div>
    </div>
</div>

<!-- Quantity -->
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
        <input type="number" name="quantity" id="qtyInput" class="form-control @error('quantity') is-invalid @enderror"
               value="{{ old('quantity', 1) }}" min="1" required>
        @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div id="qtyExceedsAlert" class="text-danger small mt-1 d-none">
            <i class="fa fa-triangle-exclamation me-1"></i>
            Quantity exceeds available stock (<span id="maxQtyLabel">0</span> units).
        </div>
    </div>
    <div class="col-md-8">
        <!-- After transfer preview -->
        <div id="afterPreview" class="p-3 rounded-3 bg-warning bg-opacity-10 mt-4" style="display:none">
            <div class="small text-muted">After transfer - Source will have:</div>
            <div class="fw-bold text-warning" id="afterFromStock">-</div>
        </div>
    </div>
</div>

{{-- No stock in source alert --}}
<div id="noStockAlert" class="alert alert-danger py-2 small d-none mb-3">
    <i class="fa fa-circle-xmark me-1"></i>
    The source warehouse has <strong>0 units</strong> of this item. Transfer is not possible.
</div>

<!-- Notes -->
<div class="mb-4">
    <label class="form-label">Notes</label>
    <input type="text" name="notes" class="form-control" value="{{ old('notes') }}"
           placeholder="e.g. Replenish Mekelle branch, customer order...">
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-warning px-4">
        <i class="fa fa-right-left me-1"></i> Transfer Stock
    </button>
    <a href="{{ route('inventory.transfers.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>

</form>
</div>
</div>

</div>
</div>

@endsection

@push('scripts')
<script>
const stockUrl          = '{{ route("inventory.transfers.warehouse-stock") }}';
const partRadio         = document.getElementById('typePart');
const vehicleRadio      = document.getElementById('typeVehicle');
const partSec           = document.getElementById('partSection');
const vehicleSec        = document.getElementById('vehicleSection');
const partSel           = document.getElementById('partSelect');
const vehicleSel        = document.getElementById('vehicleSelect');
const fromSel           = document.getElementById('fromWarehouse');
const toSel             = document.getElementById('toWarehouse');
const qtyInput          = document.getElementById('qtyInput');
const stockInfo         = document.getElementById('stockInfo');
const afterPreview      = document.getElementById('afterPreview');
const sameWarehouseAlert = document.getElementById('sameWarehouseAlert');
const noStockAlert      = document.getElementById('noStockAlert');
const qtyExceedsAlert   = document.getElementById('qtyExceedsAlert');
const submitBtn         = document.querySelector('button[type="submit"]');

let fromStockVal = 0;
let toStockVal   = 0;

function getItemType() { return vehicleRadio.checked ? 'vehicle' : 'spare_part'; }
function getItemSel()  { return vehicleRadio.checked ? vehicleSel : partSel; }
function getItemId()   { return getItemSel().value; }

function toggleType() {
    const isVehicle = vehicleRadio.checked;
    partSec.style.display    = isVehicle ? 'none' : '';
    vehicleSec.style.display = isVehicle ? '' : 'none';
    // TomSelect: enable active, disable inactive
    if (partSel._tomSelect && vehicleSel._tomSelect) {
        if (isVehicle) { partSel._tomSelect.disable(); vehicleSel._tomSelect.enable(); }
        else           { vehicleSel._tomSelect.disable(); partSel._tomSelect.enable(); }
    } else {
        partSel.disabled    = isVehicle;
        vehicleSel.disabled = !isVehicle;
    }
    refreshStocks();
}

function checkSameWarehouse() {
    const same = fromSel.value && toSel.value && fromSel.value === toSel.value;
    sameWarehouseAlert.classList.toggle('d-none', !same);
    return same;
}

function refreshStocks() {
    if (checkSameWarehouse()) {
        stockInfo.style.display    = 'none';
        afterPreview.style.display = 'none';
        noStockAlert.classList.add('d-none');
        updateSubmitState();
        return;
    }

    const itemId   = getItemId();
    const itemType = getItemType();
    const fromId   = fromSel.value;
    const toId     = toSel.value;

    if (!itemId || !fromId || !toId) {
        stockInfo.style.display    = 'none';
        afterPreview.style.display = 'none';
        noStockAlert.classList.add('d-none');
        updateSubmitState();
        return;
    }

    stockInfo.style.display = '';
    document.getElementById('fromStock').textContent = '...';
    document.getElementById('toStock').textContent   = '...';
    document.getElementById('fromWarehouseName').textContent = fromSel.options[fromSel.selectedIndex].text.replace(' (Default)', '');
    document.getElementById('toWarehouseName').textContent   = toSel.options[toSel.selectedIndex].text.replace(' (Default)', '');

    const fetchFrom = fetch(`${stockUrl}?warehouse_id=${fromId}&item_type=${itemType}&item_id=${itemId}`)
        .then(r => r.json()).then(d => {
            fromStockVal = d.stock;
            const el = document.getElementById('fromStock');
            el.textContent = d.stock;
            el.className   = 'fs-4 fw-bold ' + (d.stock <= 0 ? 'text-danger' : 'text-primary');
            // Show no-stock alert
            noStockAlert.classList.toggle('d-none', d.stock > 0);
        });

    const fetchTo = fetch(`${stockUrl}?warehouse_id=${toId}&item_type=${itemType}&item_id=${itemId}`)
        .then(r => r.json()).then(d => {
            toStockVal = d.stock;
            document.getElementById('toStock').textContent = d.stock;
        });

    Promise.all([fetchFrom, fetchTo]).then(() => {
        updateAfterPreview();
        updateSubmitState();
    });
}

function updateAfterPreview() {
    const qty = parseInt(qtyInput.value || 0);

    // Qty exceeds stock alert
    const exceeds = qty > fromStockVal && fromStockVal > 0;
    qtyExceedsAlert.classList.toggle('d-none', !exceeds);
    document.getElementById('maxQtyLabel').textContent = fromStockVal;

    if (!getItemId() || !fromSel.value || qty <= 0) {
        afterPreview.style.display = 'none';
        return;
    }

    const remaining = fromStockVal - qty;
    afterPreview.style.display = '';
    document.getElementById('afterFromStock').textContent = remaining + ' unit(s)';
    document.getElementById('afterFromStock').className =
        'fw-bold ' + (remaining < 0 ? 'text-danger' : (remaining === 0 ? 'text-warning' : 'text-success'));

    updateSubmitState();
}

function updateSubmitState() {
    const qty        = parseInt(qtyInput.value || 0);
    const sameWh     = fromSel.value && toSel.value && fromSel.value === toSel.value;
    const noStock    = fromStockVal <= 0 && getItemId() !== '';
    const exceeds    = qty > fromStockVal && fromStockVal > 0;
    const blocked    = sameWh || noStock || exceeds;

    submitBtn.disabled = blocked;
    submitBtn.title    = blocked
        ? (sameWh ? 'Same warehouse selected' : (noStock ? 'No stock in source' : 'Quantity exceeds available stock'))
        : '';
}

// Enforce max on qty input
qtyInput.addEventListener('input', function () {
    if (fromStockVal > 0 && parseInt(this.value) > fromStockVal) {
        this.value = fromStockVal;
    }
    updateAfterPreview();
});

partRadio.addEventListener('change', toggleType);
vehicleRadio.addEventListener('change', toggleType);
partSel.addEventListener('change', refreshStocks);
vehicleSel.addEventListener('change', refreshStocks);
fromSel.addEventListener('change', refreshStocks);
toSel.addEventListener('change', () => { checkSameWarehouse(); refreshStocks(); });
qtyInput.addEventListener('change', updateAfterPreview);

// Hook TomSelect onChange after global init (runs after @stack scripts)
document.addEventListener('DOMContentLoaded', function () {
    if (partSel._tomSelect)    partSel._tomSelect.on('change', refreshStocks);
    if (vehicleSel._tomSelect) vehicleSel._tomSelect.on('change', refreshStocks);
    if (fromSel._tomSelect)    fromSel._tomSelect.on('change', refreshStocks);
    if (toSel._tomSelect)      toSel._tomSelect.on('change', function () { checkSameWarehouse(); refreshStocks(); });
});

toggleType(); // init
</script>
@endpush
