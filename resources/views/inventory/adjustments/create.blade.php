@extends('layouts.app')
@section('title', 'New Stock Adjustment')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('inventory.adjustments.index') }}">Adjustments</a></li>
    <li class="breadcrumb-item active">New</li>
@endsection
@section('content')
@include('partials.page-header', ['title' => 'New Stock Adjustment', 'subtitle' => $number])

<form method="POST" action="{{ route('inventory.adjustments.store') }}" id="adjForm">
@csrf
<div class="row g-3">

<!-- Main -->
<div class="col-12 col-lg-8">
<div class="card mb-3">
<div class="card-header"><i class="fa fa-sliders me-2 text-primary"></i>Adjustment Details</div>
<div class="card-body">
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Adj. Number</label>
        <input type="text" class="form-control" value="{{ $number }}" readonly>
    </div>
    <div class="col-md-4">
        <label class="form-label">Date <span class="text-danger">*</span></label>
        <input type="date" name="adjustment_date" class="form-control @error('adjustment_date') is-invalid @enderror"
               value="{{ old('adjustment_date', today()->format('Y-m-d')) }}" required>
        @error('adjustment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Type <span class="text-danger">*</span></label>
        <select name="adjustment_type" id="adjTypeSelect" class="form-select ts-select @error('adjustment_type') is-invalid @enderror" required>
            <option value="">Select type...</option>
            <option value="increase" {{ old('adjustment_type') === 'increase' ? 'selected' : '' }}>Increase (+)</option>
            <option value="decrease" {{ old('adjustment_type') === 'decrease' ? 'selected' : '' }}>Decrease (-)</option>
        </select>
        @error('adjustment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Warehouse <span class="text-danger">*</span></label>
        <select name="warehouse_id" id="warehouseSelect" class="form-select ts-select @error('warehouse_id') is-invalid @enderror">
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}" {{ old('warehouse_id', $defaultWarehouse?->id) == $wh->id ? 'selected' : '' }}>
                    {{ $wh->name }}{{ $wh->is_default ? ' (Default)' : '' }}
                </option>
            @endforeach
        </select>
        @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Reason <span class="text-danger">*</span></label>
        <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="2"
                  placeholder="e.g. Physical count correction, Damaged items write-off...">{{ old('reason') }}</textarea>
        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
</div>
</div>

<!-- Items Cards -->
<div class="card">
<div class="card-header d-flex align-items-center justify-content-between">
    <span><i class="fa fa-list me-2 text-primary"></i>Items to Adjust</span>
    <button type="button" class="btn btn-sm btn-outline-primary" id="addAdjItem">
        <i class="fa fa-plus me-1"></i>Add Item
    </button>
</div>
<div class="card-body p-2" id="adjItemsContainer">
    {{-- Cards injected by JS --}}
</div>
</div>
</div>

<!-- Side -->
<div class="col-12 col-lg-4">
<div class="card">
<div class="card-header"><i class="fa fa-circle-info me-2 text-primary"></i>Summary</div>
<div class="card-body">
    <div class="p-3 rounded-3 bg-warning bg-opacity-10 mb-3 text-center small">
        <i class="fa fa-triangle-exclamation text-warning me-1"></i>
        Adjustments are applied immediately and cannot be undone.
        Make sure all quantities are correct before saving.
    </div>
    <div class="mb-2 small">
        <span class="text-muted">Adjustment #:</span>
        <strong class="float-end">{{ $number }}</strong>
    </div>
    <div class="mb-2 small">
        <span class="text-muted">Warehouse:</span>
        <strong class="float-end" id="selectedWarehouseName">{{ $defaultWarehouse?->name ?? '-' }}</strong>
    </div>
    <div class="mb-2 small">
        <span class="text-muted">Created by:</span>
        <strong class="float-end">{{ auth()->user()->name }}</strong>
    </div>
    <hr>
    <div class="d-grid">
        <button type="submit" class="btn btn-primary" id="saveAdjBtn">
            <i class="fa fa-save me-1"></i>Save Adjustment
        </button>
    </div>
    <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
</div>
</div>
</div>

</div>
</form>

<style>
.adj-card {
    background: #f8f9ff;
    border: 1px solid #e2e6f0;
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 10px;
    position: relative;
}
.adj-card:last-child { margin-bottom: 0; }
.adj-card .adj-num {
    font-size: .72rem;
    font-weight: 700;
    color: var(--brand-1);
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 10px;
}
.adj-card .btn-remove-adj {
    position: absolute;
    top: 10px;
    right: 12px;
    padding: 2px 8px;
    font-size: .78rem;
}
.stock-info-box {
    background: #fff;
    border: 1px solid #e2e6f0;
    border-radius: 8px;
    padding: 10px 14px;
    min-width: 130px;
}
.result-box {
    background: #fff;
    border: 1px solid #e2e6f0;
    border-radius: 8px;
    padding: 10px 14px;
    min-width: 130px;
}
</style>

@endsection
@push('scripts')
<script>
(function() {
'use strict';

const WAREHOUSE_ITEMS_URL = '{{ route("sales.ajax.warehouse-items") }}';
const WAREHOUSE_STOCK_URL = '{{ route("transfers.warehouse-stock") }}';

let rowCount = 0;
let loadedWarehouse  = null;
let VEHICLES         = [];
let CATEGORIES       = [];

const container       = document.getElementById('adjItemsContainer');
const warehouseSel    = document.getElementById('warehouseSelect');
const adjTypeSel      = document.getElementById('adjTypeSelect');

function getWarehouseId() {
    return warehouseSel?._tomSelect ? warehouseSel._tomSelect.getValue() : warehouseSel?.value;
}

function getAdjType() {
    return adjTypeSel?._tomSelect ? adjTypeSel._tomSelect.getValue() : adjTypeSel?.value;
}

// ── Load warehouse items (same as sale form) ──────────────────
function loadWarehouseItems(whId, cb) {
    if (loadedWarehouse === whId && (VEHICLES.length || CATEGORIES.length)) { if (cb) cb(); return; }
    fetch(WAREHOUSE_ITEMS_URL + '?warehouse_id=' + whId)
        .then(r => r.json())
        .then(data => {
            VEHICLES   = data.vehicles || [];
            CATEGORIES = data.categories || [];
            loadedWarehouse = whId;
            if (cb) cb();
        })
        .catch(() => { if (cb) cb(); });
}

function buildItemOptions(type, excludeIds) {
    excludeIds = excludeIds || [];
    let html = '<option value="">— Select item —</option>';
    if (type === 'vehicle') {
        VEHICLES.forEach(function(vt) {
            if (!vt.models || !vt.models.length) return;
            html += '<optgroup label="' + esc(vt.name) + '">';
            vt.models.forEach(function(m) {
                const dis = excludeIds.includes(String(m.id)) ? ' disabled' : '';
                html += '<option value="' + m.id + '" data-stock="' + m.stock + '"' + dis + '>'
                      + esc(m.name) + ' — ' + m.stock + ' unsold</option>';
            });
            html += '</optgroup>';
        });
    } else {
        CATEGORIES.forEach(function(cat) {
            if (!cat.parts || !cat.parts.length) return;
            html += '<optgroup label="' + esc(cat.name) + '">';
            cat.parts.forEach(function(p) {
                const dis = excludeIds.includes(String(p.id)) ? ' disabled' : '';
                html += '<option value="' + p.id + '" data-stock="' + p.stock + '"' + dis + '>'
                      + esc(p.name) + ' — ' + p.stock + ' unsold</option>';
            });
            html += '</optgroup>';
        });
    }
    return html;
}

function getSelectedItemIds(type, excludeCard) {
    const ids = [];
    container.querySelectorAll('.adj-card').forEach(function(c) {
        if (c === excludeCard) return;
        if (c.querySelector('.inp-type')?.value === type) {
            const id = c.querySelector('.inp-item-id')?.value;
            if (id) ids.push(String(id));
        }
    });
    return ids;
}

function syncItemDropdowns() {
    container.querySelectorAll('.adj-card').forEach(function(card) {
        const type    = card.querySelector('.inp-type')?.value;
        const selItem = card.querySelector('.sel-item');
        const curVal  = card.querySelector('.inp-item-id')?.value;
        if (!type || !selItem || selItem.disabled) return;
        const excludeIds = getSelectedItemIds(type, card);
        selItem.innerHTML = buildItemOptions(type, excludeIds);
        if (curVal) {
            for (let i = 0; i < selItem.options.length; i++) {
                if (selItem.options[i].value === curVal) { selItem.selectedIndex = i; break; }
            }
        }
    });
}

// ── Create one item card ──────────────────────────────────────
function createCard() {
    const idx = rowCount++;
    const div = document.createElement('div');
    div.className     = 'adj-card';
    div.dataset.index = idx;

    div.innerHTML =
        '<input type="hidden" name="items['+idx+'][item_type]" class="inp-type" value="">' +
        '<input type="hidden" name="items['+idx+'][item_id]"   class="inp-item-id" value="">' +

        '<div class="adj-num">Item #' + (idx + 1) + '</div>' +
        '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-adj" title="Remove">' +
            '<i class="fa fa-times"></i>' +
        '</button>' +

        // Type + Item
        '<div class="row g-2 mb-3">' +
            '<div class="col-sm-3 col-md-2">' +
                '<label class="form-label small mb-1">Type</label>' +
                '<select class="form-select form-select-sm sel-type">' +
                    '<option value="">Select…</option>' +
                    '<option value="spare_part">Spare Part</option>' +
                    '<option value="vehicle">Vehicle</option>' +
                '</select>' +
            '</div>' +
            '<div class="col-sm-9 col-md-10">' +
                '<label class="form-label small mb-1">Item</label>' +
                '<select class="form-select form-select-sm sel-item" disabled>' +
                    '<option value="">— Choose type first —</option>' +
                '</select>' +
            '</div>' +
        '</div>' +

        // Stock info + Qty + Result row
        '<div class="row g-2 align-items-end">' +
            '<div class="col-auto">' +
                '<label class="form-label small mb-1">Current Unsold Stock</label>' +
                '<div class="stock-info-box">' +
                    '<div class="text-muted" style="font-size:.7rem">Unsold qty</div>' +
                    '<div class="fw-bold fs-5 current-stock-lbl text-muted">—</div>' +
                '</div>' +
            '</div>' +
            '<div class="col-auto">' +
                '<label class="form-label small mb-1">Qty to Adjust</label>' +
                '<input type="number" name="items['+idx+'][quantity]" class="form-control form-control-sm inp-qty" ' +
                       'value="1" min="1" style="width:90px" disabled>' +
            '</div>' +
            '<div class="col-auto">' +
                '<label class="form-label small mb-1">Result</label>' +
                '<div class="result-box">' +
                    '<div class="text-muted" style="font-size:.7rem">After adj.</div>' +
                    '<div class="fw-bold fs-5 result-lbl text-muted">—</div>' +
                '</div>' +
            '</div>' +
            '<div class="col">' +
                '<label class="form-label small mb-1">Notes</label>' +
                '<input type="text" name="items['+idx+'][notes]" class="form-control form-control-sm inp-notes" placeholder="Optional...">' +
            '</div>' +
        '</div>';

    bindCard(div);
    return div;
}

function bindCard(card) {
    const selType   = card.querySelector('.sel-type');
    const selItem   = card.querySelector('.sel-item');
    const inpType   = card.querySelector('.inp-type');
    const inpItemId = card.querySelector('.inp-item-id');
    const inpQty    = card.querySelector('.inp-qty');
    const stockLbl  = card.querySelector('.current-stock-lbl');
    const resultLbl = card.querySelector('.result-lbl');
    const btnRemove = card.querySelector('.btn-remove-adj');

    function updateResult() {
        const stock   = parseInt(stockLbl.dataset.stock ?? '-1');
        const qty     = parseInt(inpQty.value) || 0;
        const adjType = getAdjType();
        if (stock < 0 || !adjType) { resultLbl.textContent = '—'; resultLbl.className = 'fw-bold fs-5 result-lbl text-muted'; return; }

        let result;
        if (adjType === 'increase') {
            result = stock + qty;
        } else {
            result = stock - qty;
            // Clamp qty so result never goes below 0
            if (result < 0) {
                inpQty.value = stock;
                result = 0;
            }
        }
        resultLbl.textContent = result;
        resultLbl.className = 'fw-bold fs-5 result-lbl ' + (result <= 0 ? 'text-danger' : 'text-success');
    }

    function setStock(val) {
        const n = parseInt(val);
        stockLbl.dataset.stock = n;
        stockLbl.textContent   = n;
        stockLbl.className     = 'fw-bold fs-5 current-stock-lbl ' + (n <= 0 ? 'text-danger' : (n <= 5 ? 'text-warning' : 'text-success'));

        // For decrease: cap qty at current stock, disable if stock is 0
        const adjType = getAdjType();
        if (adjType === 'decrease') {
            if (n <= 0) {
                inpQty.disabled = true;
                inpQty.value    = 0;
            } else {
                inpQty.disabled = false;
                inpQty.max      = n;
                if (parseInt(inpQty.value) > n) inpQty.value = n;
            }
        } else {
            inpQty.disabled = false;
            inpQty.removeAttribute('max');
        }
        updateResult();
    }

    selType.addEventListener('change', function() {
        const type = this.value;
        inpType.value   = type;
        inpItemId.value = '';
        stockLbl.textContent = '—';
        delete stockLbl.dataset.stock;
        resultLbl.textContent = '—';
        resultLbl.className = 'fw-bold fs-5 result-lbl text-muted';
        inpQty.disabled = true;

        const whId = getWarehouseId();
        if (!whId || !type) { selItem.innerHTML = '<option value="">— Choose type first —</option>'; selItem.disabled = true; return; }

        loadWarehouseItems(whId, function() {
            selItem.innerHTML = buildItemOptions(type, getSelectedItemIds(type, card));
            selItem.disabled  = false;
        });
        syncItemDropdowns();
    });

    selItem.addEventListener('change', function() {
        inpItemId.value = this.value;
        const opt = this.options[this.selectedIndex];
        if (!this.value) { stockLbl.textContent = '—'; delete stockLbl.dataset.stock; inpQty.disabled = true; syncItemDropdowns(); return; }

        const itemType = inpType.value;
        const whId     = getWarehouseId();
        stockLbl.textContent = '...';
        inpQty.disabled = true;

        fetch(WAREHOUSE_STOCK_URL + '?warehouse_id=' + whId + '&item_type=' + itemType + '&item_id=' + this.value)
            .then(r => r.json())
            .then(data => { setStock(data.stock ?? 0); })
            .catch(() => { setStock(parseInt(opt?.dataset?.stock ?? 0)); });

        syncItemDropdowns();
    });

    inpQty.addEventListener('input', function() {
        const stock   = parseInt(stockLbl.dataset.stock ?? '-1');
        const adjType = getAdjType();
        if (adjType === 'decrease' && stock >= 0) {
            if (parseInt(this.value) > stock) { this.value = stock; this.style.borderColor = '#dc2626'; setTimeout(() => { this.style.borderColor = ''; }, 1500); }
            if (parseInt(this.value) < 1) this.value = 1;
        }
        updateResult();
    });

    btnRemove.addEventListener('click', function() {
        if (container.querySelectorAll('.adj-card').length > 1) {
            card.remove();
            renumber();
            syncItemDropdowns();
        } else {
            alert('At least one item is required.');
        }
    });
}

function renumber() {
    container.querySelectorAll('.adj-card .adj-num').forEach(function(el, i) {
        el.textContent = 'Item #' + (i + 1);
    });
}

// ── Re-evaluate all qty limits when type changes ──────────────
adjTypeSel.addEventListener('change', function() {
    container.querySelectorAll('.adj-card').forEach(function(card) {
        const stock   = parseInt(card.querySelector('.current-stock-lbl')?.dataset?.stock ?? '-1');
        const inpQty  = card.querySelector('.inp-qty');
        const result  = card.querySelector('.result-lbl');
        const adjType = adjTypeSel.value;
        if (stock < 0 || !inpQty) return;

        if (adjType === 'decrease') {
            if (stock <= 0) { inpQty.disabled = true; inpQty.value = 0; }
            else { inpQty.disabled = false; inpQty.max = stock; if (parseInt(inpQty.value) > stock) inpQty.value = stock; }
        } else {
            inpQty.disabled = false;
            inpQty.removeAttribute('max');
        }

        // Update result label
        if (stock >= 0 && adjType) {
            const qty = parseInt(inpQty.value) || 0;
            const res = adjType === 'increase' ? stock + qty : Math.max(0, stock - qty);
            result.textContent = res;
            result.className = 'fw-bold fs-5 result-lbl ' + (res <= 0 ? 'text-danger' : 'text-success');
        }
    });
});

// ── Warehouse change ──────────────────────────────────────────
warehouseSel.addEventListener('change', function() {
    loadedWarehouse = null;
    const lbl = document.getElementById('selectedWarehouseName');
    if (lbl) lbl.textContent = this.options[this.selectedIndex]?.text?.replace(' (Default)', '') || '-';
    // Rebuild all cards
    container.querySelectorAll('.adj-card').forEach(function(card) {
        const type = card.querySelector('.inp-type').value;
        if (type) {
            const selType = card.querySelector('.sel-type');
            selType.dispatchEvent(new Event('change'));
        }
    });
});

// TomSelect hooks
setTimeout(function() {
    if (warehouseSel._tomSelect) warehouseSel._tomSelect.on('change', function(val) {
        loadedWarehouse = null;
        const lbl = document.getElementById('selectedWarehouseName');
        const opt = warehouseSel.querySelector('option[value="'+val+'"]');
        if (lbl && opt) lbl.textContent = opt.text.replace(' (Default)', '');
        container.querySelectorAll('.adj-card').forEach(function(card) {
            const type = card.querySelector('.inp-type').value;
            if (type) card.querySelector('.sel-type').dispatchEvent(new Event('change'));
        });
    });
    if (adjTypeSel._tomSelect) adjTypeSel._tomSelect.on('change', function() {
        adjTypeSel.dispatchEvent(new Event('change'));
    });
}, 600);

// ── Add button ────────────────────────────────────────────────
document.getElementById('addAdjItem').addEventListener('click', function() {
    const whId = getWarehouseId();
    if (!whId) { alert('Please select a warehouse first.'); return; }
    container.appendChild(createCard());
});

// ── Submit validation ─────────────────────────────────────────
document.getElementById('adjForm').addEventListener('submit', function(e) {
    let errors = [];
    container.querySelectorAll('.adj-card').forEach(function(card, idx) {
        const type = card.querySelector('.inp-type').value;
        const id   = card.querySelector('.inp-item-id').value;
        const qty  = parseInt(card.querySelector('.inp-qty').value) || 0;
        const stock = parseInt(card.querySelector('.current-stock-lbl').dataset.stock ?? '-1');
        const adjType = getAdjType();

        if (!type || !id) { errors.push('Please select a type and item for row #' + (idx + 1) + '.'); return; }
        if (qty < 1)       { errors.push('Quantity must be at least 1 for row #' + (idx + 1) + '.'); return; }
        if (adjType === 'decrease' && stock >= 0 && qty > stock) {
            errors.push('Row #' + (idx + 1) + ': cannot decrease by ' + qty + ' — only ' + stock + ' unsold in this warehouse.');
        }
    });
    if (errors.length) { e.preventDefault(); alert(errors[0]); }
});

function esc(str) { return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ── Init ──────────────────────────────────────────────────────
container.appendChild(createCard());

})();
</script>
@endpush
