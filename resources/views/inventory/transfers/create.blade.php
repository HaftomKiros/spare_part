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

<!-- Item Type + Item selector -->
<div class="row g-3 mb-3">
    <div class="col-sm-3 col-md-2">
        <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
        <select id="typeSelect" class="form-select">
            <option value="">Select…</option>
            <option value="spare_part" {{ old('item_type', 'spare_part') === 'spare_part' ? 'selected' : '' }}>Spare Part</option>
            <option value="vehicle"    {{ old('item_type') === 'vehicle' ? 'selected' : '' }}>Vehicle</option>
        </select>
        <input type="hidden" name="item_type" id="itemTypeHidden" value="{{ old('item_type', 'spare_part') }}">
    </div>
    <div class="col-sm-9 col-md-10">
        <label class="form-label fw-semibold">Item <span class="text-danger">*</span></label>
        <select name="item_id" id="itemSelect" class="form-select @error('item_id') is-invalid @enderror" disabled>
            <option value="">— Select type first —</option>
        </select>
        @error('item_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

{{-- No items warning --}}
<div id="noItemsAlert" class="alert alert-warning py-2 small d-none mb-3">
    <i class="fa fa-triangle-exclamation me-1"></i>
    No items of this type have unsold purchase batches in the selected warehouse.
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
const stockUrl         = '{{ route("inventory.transfers.warehouse-stock") }}';
const itemsUrl         = '{{ route("inventory.transfers.warehouse-items") }}';
const typeSelect       = document.getElementById('typeSelect');
const itemTypeHidden   = document.getElementById('itemTypeHidden');
const itemSel          = document.getElementById('itemSelect');
const fromSel          = document.getElementById('fromWarehouse');
const toSel            = document.getElementById('toWarehouse');
const qtyInput         = document.getElementById('qtyInput');
const stockInfo        = document.getElementById('stockInfo');
const afterPreview     = document.getElementById('afterPreview');
const sameWhAlert      = document.getElementById('sameWarehouseAlert');
const noStockAlert     = document.getElementById('noStockAlert');
const noItemsAlert     = document.getElementById('noItemsAlert');
const qtyExceedsAlert  = document.getElementById('qtyExceedsAlert');
const submitBtn        = document.querySelector('button[type="submit"]');

let fromStockVal = 0;

function getItemType() { return typeSelect.value || 'spare_part'; }
function getItemId()   { return itemSel.value; }

// ── Load items filtered by warehouse + unsold batches ─────────
function loadItems() {
    const whId = fromSel.value;
    const type = getItemType();

    itemTypeHidden.value = type;
    itemSel.innerHTML    = '<option value="">Loading...</option>';
    itemSel.disabled     = true;
    noItemsAlert.classList.add('d-none');

    if (!whId || !type) {
        itemSel.innerHTML = '<option value="">— Select warehouse and type —</option>';
        updateSubmitState();
        return;
    }

    fetch(itemsUrl + '?warehouse_id=' + whId + '&item_type=' + type)
        .then(r => r.json())
        .then(items => {
            if (!items.length) {
                itemSel.innerHTML = '<option value="">— No items available —</option>';
                itemSel.disabled  = true;
                noItemsAlert.classList.remove('d-none');
            } else {
                let html = '<option value="">— Choose item —</option>';
                items.forEach(i => {
                    html += '<option value="' + i.id + '" data-stock="' + i.stock + '">' + i.label + '</option>';
                });
                itemSel.innerHTML = html;
                itemSel.disabled  = false;
                noItemsAlert.classList.add('d-none');
            }
            resetStockDisplay();
            updateSubmitState();
        })
        .catch(() => {
            itemSel.innerHTML = '<option value="">Error loading items</option>';
            updateSubmitState();
        });
}

function resetStockDisplay() {
    stockInfo.style.display    = 'none';
    afterPreview.style.display = 'none';
    noStockAlert.classList.add('d-none');
    fromStockVal = 0;
}

function checkSameWarehouse() {
    const same = fromSel.value && toSel.value && fromSel.value === toSel.value;
    sameWhAlert.classList.toggle('d-none', !same);
    return same;
}

function refreshStocks() {
    if (checkSameWarehouse()) { resetStockDisplay(); updateSubmitState(); return; }

    const itemId   = getItemId();
    const itemType = getItemType();
    const fromId   = fromSel.value;
    const toId     = toSel.value;

    if (!itemId || !fromId || !toId) { resetStockDisplay(); updateSubmitState(); return; }

    stockInfo.style.display = '';
    document.getElementById('fromStock').textContent = '...';
    document.getElementById('toStock').textContent   = '...';
    document.getElementById('fromWarehouseName').textContent = fromSel.options[fromSel.selectedIndex].text.replace(' (Default)','');
    document.getElementById('toWarehouseName').textContent   = toSel.options[toSel.selectedIndex].text.replace(' (Default)','');

    Promise.all([
        fetch(`${stockUrl}?warehouse_id=${fromId}&item_type=${itemType}&item_id=${itemId}`).then(r=>r.json()),
        fetch(`${stockUrl}?warehouse_id=${toId}&item_type=${itemType}&item_id=${itemId}`).then(r=>r.json()),
    ]).then(([from, to]) => {
        fromStockVal = from.stock;
        const el = document.getElementById('fromStock');
        el.textContent = from.stock;
        el.className   = 'fs-4 fw-bold ' + (from.stock <= 0 ? 'text-danger' : 'text-primary');
        noStockAlert.classList.toggle('d-none', from.stock > 0);

        document.getElementById('toStock').textContent = to.stock;
        updateAfterPreview();
        updateSubmitState();
    });
}

function updateAfterPreview() {
    const qty     = parseInt(qtyInput.value || 0);
    const exceeds = qty > fromStockVal && fromStockVal > 0;
    qtyExceedsAlert.classList.toggle('d-none', !exceeds);
    document.getElementById('maxQtyLabel').textContent = fromStockVal;

    if (!getItemId() || !fromSel.value || qty <= 0) { afterPreview.style.display = 'none'; return; }

    const remaining = fromStockVal - qty;
    afterPreview.style.display = '';
    document.getElementById('afterFromStock').textContent = remaining + ' unit(s)';
    document.getElementById('afterFromStock').className =
        'fw-bold ' + (remaining < 0 ? 'text-danger' : remaining === 0 ? 'text-warning' : 'text-success');
    updateSubmitState();
}

function updateSubmitState() {
    const qty      = parseInt(qtyInput.value || 0);
    const sameWh   = fromSel.value && toSel.value && fromSel.value === toSel.value;
    const noItems  = itemSel.disabled;
    const noStock  = fromStockVal <= 0 && getItemId() !== '';
    const exceeds  = qty > fromStockVal && fromStockVal > 0;
    const noType   = !typeSelect.value;
    const blocked  = sameWh || noItems || noStock || exceeds || noType || !fromSel.value;

    submitBtn.disabled = blocked;
    submitBtn.title    = blocked
        ? (sameWh ? 'Same warehouse' : noType ? 'Select a type' : noItems ? 'No items available'
           : noStock ? 'No stock in source' : 'Quantity exceeds stock') : '';
}

// Events
typeSelect.addEventListener('change', function() {
    itemTypeHidden.value = this.value;
    loadItems();
});
itemSel.addEventListener('change', refreshStocks);
fromSel.addEventListener('change', function() { loadItems(); checkSameWarehouse(); });
toSel.addEventListener('change', function()   { checkSameWarehouse(); refreshStocks(); });
qtyInput.addEventListener('input', function() {
    if (fromStockVal > 0 && parseInt(this.value) > fromStockVal) this.value = fromStockVal;
    updateAfterPreview();
});
qtyInput.addEventListener('change', updateAfterPreview);

// TomSelect hooks
document.addEventListener('DOMContentLoaded', function() {
    if (fromSel._tomSelect) fromSel._tomSelect.on('change', function() { loadItems(); checkSameWarehouse(); });
    if (toSel._tomSelect)   toSel._tomSelect.on('change', function()   { checkSameWarehouse(); refreshStocks(); });
});

// Init
updateSubmitState();
</script>
@endpush
