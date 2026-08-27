@extends('layouts.app')
@section('title','New Sale')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
    <li class="breadcrumb-item active">New Sale</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'New Sale','subtitle'=>'Invoice '.$invoice])

<form method="POST" action="{{ route('sales.store') }}" id="saleForm" data-no-loader>
@csrf

@if($poNumber)
<div class="alert alert-info py-2 mb-3 small">
    <i class="fa fa-box me-1"></i>
    Selling from purchase <strong>{{ $poNumber }}</strong> — items have been pre-loaded below. You can adjust quantities before completing.
</div>
@endif

{{-- Hidden totals filled by JS before submit --}}
<input type="hidden" name="subtotal" id="subtotalInput" value="0">
<input type="hidden" name="tax"      id="taxAmountInput" value="0">
<input type="hidden" name="total"    id="totalInput" value="0">
<input type="hidden" name="balance"  id="balanceInput" value="0">

<div class="row g-3">

{{-- LEFT --}}
<div class="col-12 col-lg-8">

{{-- Sale Header --}}
<div class="card mb-3">
<div class="card-header"><i class="fa fa-receipt me-2" style="color:var(--brand-1)"></i>Sale Information</div>
<div class="card-body">
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Invoice Number</label>
        <input type="text" class="form-control" value="{{ $invoice }}" readonly>
    </div>
    <div class="col-md-4">
        <label class="form-label">Date <span class="text-danger">*</span></label>
        <input type="date" name="sale_date" class="form-control @error('sale_date') is-invalid @enderror"
               value="{{ old('sale_date', today()->format('Y-m-d')) }}" required>
        @error('sale_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Customer</label>
        <select name="customer_id" class="form-select ts-select">
            <option value="">Walk-in Customer</option>
            @foreach($customers as $c)
                <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                    {{ $c->name }} ({{ $c->phone }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
        <select name="payment_method" class="form-select ts-select" required>
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="cheque">Cheque</option>
            <option value="credit">Credit</option>
        </select>
    </div>
    @if($warehouses->count() > 1)
    <div class="col-md-6">
        <label class="form-label">Warehouse <span class="text-danger">*</span></label>
        <select name="warehouse_id" id="warehouseSelect" class="form-select ts-select" required>
            <option value="">-- Select Warehouse --</option>
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}"
                    {{ $defaultWarehouse?->id == $wh->id ? 'selected' : '' }}>
                    {{ $wh->name }}{{ $wh->city ? ' ('.$wh->city.')' : '' }}
                </option>
            @endforeach
        </select>
    </div>
    @else
        <input type="hidden" name="warehouse_id" id="warehouseSelect" value="{{ $defaultWarehouse?->id }}">
    @endif
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="1" placeholder="Optional notes..."></textarea>
    </div>
</div>
</div>
</div>

{{-- Items --}}
<div class="card">
<div class="card-header d-flex align-items-center justify-content-between">
    <span><i class="fa fa-list me-2" style="color:var(--brand-1)"></i>Sale Items</span>
    <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">
        <i class="fa fa-plus me-1"></i>Add Item
    </button>
</div>
<div class="card-body p-2" id="itemsContainer">
    {{-- Cards injected by JS --}}
</div>
</div>

</div>{{-- /col-lg-8 --}}

{{-- RIGHT --}}
<div class="col-12 col-lg-4">

<div class="card mb-3">
<div class="card-header"><i class="fa fa-calculator me-2" style="color:var(--brand-1)"></i>Totals</div>
<div class="card-body">
    <div class="d-flex justify-content-between mb-2 small">
        <span class="text-muted">Subtotal</span>
        <strong>Br <span id="subtotalDisplay">0.00</span></strong>
    </div>
    <div class="mb-2">
        <label class="form-label small mb-1">Discount (Br)</label>
        <input type="number" name="discount" id="discountInput"
               class="form-control form-control-sm" value="0" min="0" step="0.01">
    </div>
    <div class="mb-3">
        <label class="form-label small mb-1">Tax (%)</label>
        <input type="number" name="tax_rate" id="taxInput"
               class="form-control form-control-sm" value="0" min="0" max="100" step="0.01">
    </div>
    <hr class="my-2">
    <div class="d-flex justify-content-between align-items-center">
        <span class="fw-bold">Total</span>
        <strong class="fs-5" style="color:var(--brand-1)">Br <span id="totalDisplay">0.00</span></strong>
    </div>
</div>
</div>

<div class="card mb-3">
<div class="card-header"><i class="fa fa-money-bill me-2" style="color:var(--brand-1)"></i>Payment</div>
<div class="card-body">
    <div class="mb-3">
        <label class="form-label">Amount Paid (Br) <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">Br</span>
            <input type="number" name="paid_amount" id="paidInput"
                   class="form-control" value="0" min="0" step="0.01" required>
        </div>
    </div>
    <div class="d-flex justify-content-between small mb-3">
        <span class="text-muted">Balance Due</span>
        <strong class="text-danger">Br <span id="balanceDisplay">0.00</span></strong>
    </div>
    <button type="button" class="btn btn-sm btn-outline-success w-100" id="payFullBtn">
        <i class="fa fa-check-circle me-1"></i>Pay Full Amount
    </button>
</div>
</div>

<div class="card">
<div class="card-body">
    <button type="submit" class="btn btn-primary w-100 btn-lg">
        <i class="fa fa-save me-1"></i>Complete Sale
    </button>
    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
</div>
</div>

</div>{{-- /col-lg-4 --}}
</div>{{-- /row --}}
</form>

{{-- Validation Error Modal --}}
<div id="saleErrorModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center">
<div style="background:#fff;border-radius:16px;width:100%;max-width:400px;margin:16px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden">
    <div style="background:linear-gradient(135deg,#ef4444,#dc2626);padding:18px 24px 14px;display:flex;align-items:center;gap:10px">
        <div style="background:rgba(255,255,255,.2);border-radius:10px;width:36px;height:36px;display:flex;align-items:center;justify-content:center">
            <i class="fa fa-circle-xmark" style="color:#fff;font-size:1rem"></i>
        </div>
        <div style="color:#fff;font-weight:700;font-size:1rem">Validation Error</div>
    </div>
    <div style="padding:20px 24px">
        <p id="saleErrorMessage" style="margin:0;color:#374151;font-size:.95rem;line-height:1.6"></p>
    </div>
    <div style="padding:0 24px 20px;text-align:right">
        <button id="saleErrorClose" type="button"
                style="padding:9px 24px;border-radius:8px;border:none;background:#ef4444;color:#fff;font-weight:600;cursor:pointer;font-size:.9rem">
            OK, Fix It
        </button>
    </div>
</div>
</div>

{{-- Sale Confirmation Modal --}}
<div id="saleConfirmModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center">
<div style="background:#fff;border-radius:16px;width:100%;max-width:480px;margin:16px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden">

    {{-- Header --}}
    <div style="background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:20px 24px 16px">
        <div style="display:flex;align-items:center;gap:10px">
            <div style="background:rgba(255,255,255,.2);border-radius:10px;width:38px;height:38px;display:flex;align-items:center;justify-content:center">
                <i class="fa fa-receipt" style="color:#fff;font-size:1rem"></i>
            </div>
            <div>
                <div style="color:#fff;font-weight:700;font-size:1rem">Confirm Sale</div>
                <div style="color:rgba(255,255,255,.75);font-size:.8rem">Review items before completing</div>
            </div>
        </div>
    </div>

    {{-- Body --}}
    <div style="padding:20px 24px">
        <div id="saleConfirmItems" style="display:flex;flex-direction:column;gap:10px"></div>
    </div>

    {{-- Footer --}}
    <div style="padding:0 24px 20px;display:flex;gap:10px;justify-content:flex-end">
        <button id="saleConfirmCancel" type="button"
                style="padding:9px 22px;border-radius:8px;border:1.5px solid #e2e6f0;background:#fff;color:#64748b;font-weight:600;cursor:pointer;font-size:.9rem">
            Cancel
        </button>
        <button id="saleConfirmOk" type="button"
                style="padding:9px 22px;border-radius:8px;border:none;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;font-weight:600;cursor:pointer;font-size:.9rem">
            <i class="fa fa-check me-1"></i>Complete Sale
        </button>
    </div>
</div>
</div>

<style>
.item-card {
    background: #f8f9ff;
    border: 1px solid #e2e6f0;
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 10px;
    position: relative;
}
.item-card:last-child { margin-bottom: 0; }
.item-card .item-num {
    font-size: .72rem;
    font-weight: 700;
    color: var(--brand-1);
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 10px;
}
.item-card .btn-remove-card {
    position: absolute;
    top: 10px;
    right: 12px;
    padding: 2px 8px;
    font-size: .78rem;
}
.batch-block { margin-top: 8px; }
.batch-info  { font-size: .78rem; color: #6b7280; margin-top: 4px; }
</style>

@endsection
@push('scripts')
<script>
(function () {
    'use strict';

    const WAREHOUSE_ITEMS_URL  = '{{ route("sales.ajax.warehouse-items") }}';
    const PURCHASE_BATCHES_URL = '{{ route("sales.ajax.purchase-batches") }}';
    const DEFAULT_WAREHOUSE    = '{{ $defaultWarehouse?->id }}';
    const PO_ITEMS             = {!! json_encode($poItems->values()->map(fn($i) => [
        'item_type'        => $i->item_type,
        'item_id'          => $i->item_type === 'vehicle' ? $i->vehicle_model_id : $i->spare_part_id,
        'purchase_item_id' => $i->id,
        'remaining'        => $i->quantity - $i->total_sold,
        'unit_price'       => $i->unit_price,
        'item_name'        => $i->item_name,
    ])) !!};

    let VEHICLES        = [];
    let CATEGORIES      = [];
    let loadedWarehouse = null;
    let rowCount        = 0;

    const container     = document.getElementById('itemsContainer');
    const subtotalEl    = document.getElementById('subtotalDisplay');
    const totalEl       = document.getElementById('totalDisplay');
    const balanceEl     = document.getElementById('balanceDisplay');
    const subtotalInput = document.getElementById('subtotalInput');
    const taxAmtInput   = document.getElementById('taxAmountInput');
    const totalInput    = document.getElementById('totalInput');
    const balanceInput  = document.getElementById('balanceInput');
    const discountInput = document.getElementById('discountInput');
    const taxInput      = document.getElementById('taxInput');
    const paidInput     = document.getElementById('paidInput');

    function getWarehouseId() {
        const sel = document.getElementById('warehouseSelect');
        return sel ? (sel.value || DEFAULT_WAREHOUSE) : DEFAULT_WAREHOUSE;
    }

    // ── Load warehouse items (only items with purchase batches) ────
    function loadWarehouseItems(warehouseId, callback) {
        if (!warehouseId) { VEHICLES = []; CATEGORIES = []; if (callback) callback(); return; }
        if (loadedWarehouse === warehouseId) { if (callback) callback(); return; }

        fetch(WAREHOUSE_ITEMS_URL + '?warehouse_id=' + warehouseId)
            .then(r => r.json())
            .then(data => {
                VEHICLES        = data.vehicles   || [];
                CATEGORIES      = data.categories || [];
                loadedWarehouse = warehouseId;

                // Rebuild type+item on existing cards
                container.querySelectorAll('.item-card').forEach(card => {
                    const type = card.querySelector('.inp-type').value;
                    if (type) onTypeSelected(card, type);
                });

                if (callback) callback();
                recalcTotals();
            })
            .catch(() => { VEHICLES = []; CATEGORIES = []; });
    }

    // ── Check if a type has any sellable items ────────────────────
    function typeHasItems(type) {
        if (type === 'vehicle') {
            return VEHICLES.some(vt => vt.models && vt.models.length > 0);
        }
        return CATEGORIES.some(cat => cat.parts && cat.parts.length > 0);
    }

    // ── Build item <option> HTML ───────────────────────────────────
    function buildItemOptions(type) {
        let html = '<option value="">— Select item —</option>';
        if (type === 'vehicle') {
            VEHICLES.forEach(vt => {
                if (!vt.models.length) return;
                html += '<optgroup label="' + esc(vt.name) + '">';
                vt.models.forEach(m => {
                    html += '<option value="' + m.id
                         +  '" data-price="'     + (m.price_max || m.price || 0)
                         +  '" data-price-min="' + (m.price_min || 0)
                         +  '" data-price-max="' + (m.price_max || m.price || 0)
                         +  '" data-stock="'     + m.stock
                         +  '" data-reorder="'   + (m.reorder||2)
                         +  '" data-name="'      + esc(m.name) + '">'
                         +  esc(m.name) + ' — Stock: ' + m.stock + '</option>';
                });
                html += '</optgroup>';
            });
        } else {
            CATEGORIES.forEach(cat => {
                if (!cat.parts.length) return;
                html += '<optgroup label="' + esc(cat.name) + '">';
                cat.parts.forEach(p => {
                    html += '<option value="' + p.id
                         +  '" data-price="'     + p.price_max
                         +  '" data-price-min="' + p.price_min
                         +  '" data-price-max="' + p.price_max
                         +  '" data-buy-price="' + p.buy_price
                         +  '" data-stock="'     + p.stock
                         +  '" data-reorder="'   + (p.reorder||5)
                         +  '" data-name="'      + esc(p.name) + '">'
                         +  esc(p.name) + ' — Stock: ' + p.stock + (p.unit ? ' '+p.unit : '') + '</option>';
                });
                html += '</optgroup>';
            });
        }
        return html;
    }

    // ── Called when type is selected on a card ────────────────────
    function onTypeSelected(card, type) {
        const selItem  = card.querySelector('.sel-item');
        const noStock  = card.querySelector('.no-stock-warn');
        const inpType  = card.querySelector('.inp-type');
        const inpItemId= card.querySelector('.inp-item-id');
        const inpPrice = card.querySelector('.inp-price');

        inpType.value   = type;
        inpItemId.value = '';
        inpPrice.value  = '0.00';
        clearBatchArea(card);

        if (!type) {
            selItem.innerHTML = '<option value="">— Choose type first —</option>';
            selItem.disabled  = true;
            noStock.style.display = 'none';
            setCardInputsDisabled(card, false);
            checkSubmitBtn();
            return;
        }

        const whId = getWarehouseId();
        if (!whId) {
            alert('Please select a warehouse first.');
            card.querySelector('.sel-type').value = '';
            inpType.value = '';
            return;
        }

        // Load warehouse data if needed then check
        loadWarehouseItems(whId, function() {
            if (!typeHasItems(type)) {
                // No items of this type have available purchase batches
                selItem.innerHTML = '<option value="">— No items in stock —</option>';
                selItem.disabled  = true;
                noStock.style.display = 'flex';
                setCardInputsDisabled(card, true);
            } else {
                selItem.innerHTML = buildItemOptions(type);
                selItem.disabled  = false;
                noStock.style.display = 'none';
                setCardInputsDisabled(card, false);
            }
            checkSubmitBtn();
        });
    }

    // ── Load purchase batches after item selected ─────────────────
    function loadBatches(card, itemType, itemId) {
        const batchBlock = card.querySelector('.batch-block');
        const selBatch   = card.querySelector('.sel-batch');
        const batchInfo  = card.querySelector('.batch-info');
        const noBatch    = card.querySelector('.no-batch-warn');

        batchBlock.style.display = 'none';
        noBatch.style.display    = 'none';
        batchInfo.textContent    = '';
        card.querySelector('.inp-purchase-item-id').value = '';

        const whId = getWarehouseId();
        if (!itemType || !itemId || !whId) return;

        selBatch.innerHTML = '<option value="">Loading...</option>';

        fetch(PURCHASE_BATCHES_URL + '?item_type=' + itemType + '&item_id=' + itemId + '&warehouse_id=' + whId)
            .then(r => r.json())
            .then(batches => {
                if (!batches.length) {
                    noBatch.style.display = 'flex';
                    setCardInputsDisabled(card, true);
                    checkSubmitBtn();
                    return;
                }

                let html = '<option value="">— Any (FIFO auto) —</option>';
                batches.forEach(b => {
                    html += '<option value="' + b.purchase_item_id + '"'
                         +  ' data-remaining="' + b.remaining + '"'
                         +  ' data-unit-price="' + b.unit_price + '">'
                         +  esc(b.purchase_number) + ' — Remaining: ' + b.remaining
                         +  ' (@ Br ' + parseFloat(b.unit_price).toFixed(2) + ')'
                         +  '</option>';
                });
                selBatch.innerHTML = html;
                batchBlock.style.display = 'block';
                setCardInputsDisabled(card, false);

                selBatch.selectedIndex = 1;
                selBatch.dispatchEvent(new Event('change'));
                checkSubmitBtn();
            })
            .catch(() => {
                noBatch.style.display = 'flex';
                setCardInputsDisabled(card, true);
                checkSubmitBtn();
            });
    }

    function clearBatchArea(card) {
        const bb = card.querySelector('.batch-block');
        if (bb) bb.style.display = 'none';
        const sb = card.querySelector('.sel-batch');
        if (sb) sb.innerHTML = '<option value="">—</option>';
        const bi = card.querySelector('.batch-info');
        if (bi) bi.textContent = '';
        const hi = card.querySelector('.inp-purchase-item-id');
        if (hi) hi.value = '';
        const nb = card.querySelector('.no-batch-warn');
        if (nb) nb.style.display = 'none';
        const sw = card.querySelector('.stock-warn');
        if (sw) { sw.className = 'stock-warn d-none'; sw.textContent = ''; }
    }

    function setCardInputsDisabled(card, disabled) {
        ['inp-price','inp-qty','inp-disc'].forEach(cls => {
            const el = card.querySelector('.' + cls);
            if (el) el.disabled = disabled;
        });
    }

    function checkSubmitBtn() {
        const btn = document.querySelector('#saleForm button[type="submit"]');
        if (!btn) return;
        const blocked = Array.from(container.querySelectorAll('.no-batch-warn,.no-stock-warn'))
            .some(el => el.style.display !== 'none' && el.style.display !== '');
        btn.disabled = blocked;
        btn.title    = blocked ? 'Cannot save: one or more items have no available stock' : '';
    }

    // ── Create one item card ───────────────────────────────────────
    function createCard() {
        const idx = rowCount++;
        const div = document.createElement('div');
        div.className     = 'item-card';
        div.dataset.index = idx;

        div.innerHTML =
            '<input type="hidden" name="items['+idx+'][item_type]"        class="inp-type"             value="">' +
            '<input type="hidden" name="items['+idx+'][item_id]"          class="inp-item-id"          value="">' +
            '<input type="hidden" name="items['+idx+'][total]"            class="inp-total"            value="0">' +
            '<input type="hidden" name="items['+idx+'][purchase_item_id]" class="inp-purchase-item-id" value="">' +

            '<div class="item-num">Item #' + (idx+1) + '</div>' +
            '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-card" title="Remove">' +
                '<i class="fa fa-times"></i>' +
            '</button>' +

            // Type + Item row
            '<div class="row g-2 mb-2">' +
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
                    '<small class="stock-warn d-none mt-1"></small>' +
                '</div>' +
            '</div>' +

            // No stock warning (shown when type has no items with batches)
            '<div class="no-stock-warn alert alert-warning py-2 px-3 mb-2 small align-items-center gap-2" style="display:none">' +
                '<i class="fa fa-triangle-exclamation"></i>' +
                '<span>No items of this type have available stock in the selected warehouse. Add a new purchase first.</span>' +
            '</div>' +

            // No batch warning (shown when specific item has no batches)
            '<div class="no-batch-warn alert alert-danger py-2 px-3 mb-2 small align-items-center gap-2" style="display:none">' +
                '<i class="fa fa-circle-xmark"></i>' +
                '<span>All purchased stock for this item has been fully sold. Add a new purchase before selling.</span>' +
            '</div>' +

            // PO# batch dropdown
            '<div class="batch-block" style="display:none">' +
                '<div class="row g-2 mb-2">' +
                    '<div class="col-12">' +
                        '<label class="form-label small mb-1"><i class="fa fa-box me-1 text-primary"></i>Purchase Batch (PO#)</label>' +
                        '<select class="form-select form-select-sm sel-batch"><option value="">—</option></select>' +
                        '<div class="batch-info"></div>' +
                    '</div>' +
                '</div>' +
            '</div>' +

            // Price / Qty / Disc / Total
            '<div class="row g-2 align-items-end">' +
                '<div class="col-6 col-sm-3">' +
                    '<label class="form-label small mb-1">Price (Br)</label>' +
                    '<input type="number" name="items['+idx+'][unit_price]" class="form-control form-control-sm inp-price" value="0.00" min="0" step="0.01" disabled>' +
                '</div>' +
                '<div class="col-6 col-sm-2">' +
                    '<label class="form-label small mb-1">Qty</label>' +
                    '<input type="number" name="items['+idx+'][quantity]" class="form-control form-control-sm inp-qty" value="1" min="1" disabled>' +
                '</div>' +
                '<div class="col-6 col-sm-3">' +
                    '<label class="form-label small mb-1">Discount (Br)</label>' +
                    '<input type="number" name="items['+idx+'][discount]" class="form-control form-control-sm inp-disc" value="0.00" min="0" step="0.01" disabled>' +
                '</div>' +
                '<div class="col-6 col-sm-4">' +
                    '<label class="form-label small mb-1">Line Total</label>' +
                    '<div class="form-control form-control-sm bg-light fw-semibold lbl-total" style="color:var(--brand-1)">Br 0.00</div>' +
                '</div>' +
            '</div>';

        bindCard(div);
        return div;
    }

    // ── Bind events to a card ──────────────────────────────────────
    function bindCard(card) {
        const selType      = card.querySelector('.sel-type');
        const selItem      = card.querySelector('.sel-item');
        const selBatch     = card.querySelector('.sel-batch');
        const inpItemId    = card.querySelector('.inp-item-id');
        const inpPurchItem = card.querySelector('.inp-purchase-item-id');
        const inpPrice     = card.querySelector('.inp-price');
        const inpQty       = card.querySelector('.inp-qty');
        const inpDisc      = card.querySelector('.inp-disc');
        const inpTotal     = card.querySelector('.inp-total');
        const lblTotal     = card.querySelector('.lbl-total');
        const stockWarn    = card.querySelector('.stock-warn');
        const batchInfo    = card.querySelector('.batch-info');
        const btnRemove    = card.querySelector('.btn-remove-card');

        function updateRowTotal() {
            const qty   = Math.max(0, parseFloat(inpQty.value)   || 0);
            const price = Math.max(0, parseFloat(inpPrice.value) || 0);
            const disc  = Math.max(0, parseFloat(inpDisc.value)  || 0);
            const total = Math.max(0, (qty * price) - disc);
            lblTotal.textContent = 'Br ' + total.toFixed(2);
            inpTotal.value       = total.toFixed(2);
            recalcTotals();
        }

        // Type changed
        selType.addEventListener('change', function () {
            onTypeSelected(card, this.value);
            updateRowTotal();
        });

        // Item changed
        selItem.addEventListener('change', function () {
            inpItemId.value = this.value;
            stockWarn.className = 'stock-warn d-none';
            stockWarn.textContent = '';
            clearBatchArea(card);

            if (this.value) {
                const opt      = this.options[this.selectedIndex];
                const stock    = parseInt(opt.dataset.stock)    || 0;
                const reorder  = parseInt(opt.dataset.reorder)  || 0;
                const priceMax = parseFloat(opt.dataset.priceMax || opt.dataset.price || 0);
                const priceMin = parseFloat(opt.dataset.priceMin || 0);

                // Default = max price, enforce min
                inpPrice.value              = priceMax.toFixed(2);
                inpPrice.min                = priceMin.toFixed(2);
                inpPrice.dataset.priceMin   = priceMin;
                inpPrice.dataset.priceMax   = priceMax;
                inpPrice.dataset.buyPrice   = parseFloat(opt.dataset.buyPrice || 0);
                inpPrice.dataset.itemName   = opt.dataset.name || opt.text;

                inpQty.max = stock;
                if (parseInt(inpQty.value) > stock) inpQty.value = stock;

                if (stock <= 0) {
                    stockWarn.className = 'stock-warn text-danger d-block mt-1';
                    stockWarn.innerHTML = '<i class="fa fa-circle-xmark me-1"></i>Out of stock in selected warehouse';
                } else if (reorder > 0 && stock <= reorder) {
                    stockWarn.className = 'stock-warn text-warning d-block mt-1';
                    stockWarn.innerHTML = '<i class="fa fa-triangle-exclamation me-1"></i>Low stock in selected warehouse: ' + stock + ' remaining';
                } else {
                    stockWarn.className = 'stock-warn d-none';
                }

                loadBatches(card, card.querySelector('.inp-type').value, this.value);
            } else {
                inpPrice.value = '0.00';
                inpPrice.removeAttribute('min');
                delete inpPrice.dataset.priceMin;
                delete inpPrice.dataset.priceMax;
                delete inpPrice.dataset.buyPrice;
                delete inpPrice.dataset.itemName;
                inpQty.removeAttribute('max');
                setCardInputsDisabled(card, false);
            }
            updateRowTotal();
        });

        // Batch changed
        selBatch.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            inpPurchItem.value = this.value;

            if (this.value && opt.dataset.remaining !== undefined) {
                const remaining = parseInt(opt.dataset.remaining);
                inpQty.max = remaining;
                if (parseInt(inpQty.value) > remaining) inpQty.value = remaining;
                batchInfo.innerHTML = '<i class="fa fa-circle-check me-1 text-success"></i>Remaining in this batch: <strong>' + remaining + '</strong>';
            } else {
                inpPurchItem.value = '';
                const whMax = parseInt(selItem.options[selItem.selectedIndex]?.dataset?.stock) || 0;
                if (whMax) { inpQty.max = whMax; if (parseInt(inpQty.value) > whMax) inpQty.value = whMax; }
                else inpQty.removeAttribute('max');
                batchInfo.innerHTML = '<span class="text-muted">FIFO: oldest batch assigned automatically</span>';
            }
            updateRowTotal();
        });

        inpQty.addEventListener('input', function () {
            const max = parseInt(this.max);
            if (!isNaN(max) && parseInt(this.value) > max) {
                this.value = max;
                this.style.borderColor = '#dc2626';
                setTimeout(() => { inpQty.style.borderColor = ''; }, 1500);
            }
            updateRowTotal();
        });

        [inpPrice, inpDisc].forEach(el => el.addEventListener('input', updateRowTotal));

        btnRemove.addEventListener('click', function () {
            if (container.querySelectorAll('.item-card').length > 1) {
                card.remove();
                renumberCards();
                recalcTotals();
                checkSubmitBtn();
            } else {
                alert('At least one item is required.');
            }
        });
    }

    function renumberCards() {
        container.querySelectorAll('.item-card .item-num').forEach((el, i) => {
            el.textContent = 'Item #' + (i + 1);
        });
    }

    function recalcTotals() {
        let subtotal = 0;
        container.querySelectorAll('.inp-total').forEach(el => { subtotal += parseFloat(el.value) || 0; });
        const discount = Math.max(0, parseFloat(discountInput.value) || 0);
        const taxRate  = Math.max(0, parseFloat(taxInput.value)      || 0);
        const taxAmt   = ((subtotal - discount) * taxRate) / 100;
        const total    = Math.max(0, subtotal - discount + taxAmt);
        const paid     = Math.max(0, parseFloat(paidInput.value) || 0);
        const balance  = Math.max(0, total - paid);

        subtotalEl.textContent = subtotal.toFixed(2);
        totalEl.textContent    = total.toFixed(2);
        balanceEl.textContent  = balance.toFixed(2);
        subtotalInput.value    = subtotal.toFixed(2);
        taxAmtInput.value      = taxAmt.toFixed(2);
        totalInput.value       = total.toFixed(2);
        balanceInput.value     = balance.toFixed(2);
    }

    // ── Show validation error modal ────────────────────────────────
    function showError(message) {
        const modal = document.getElementById('saleErrorModal');
        document.getElementById('saleErrorMessage').textContent = message;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        const btn = document.getElementById('saleErrorClose');
        btn.onclick = function() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        };
        modal.onclick = function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        };
    }

    // ── Validate all cards, return confirmLines or null on error ───
    function validateAndBuildLines() {
        let errors = [];
        let confirmLines = [];

        container.querySelectorAll('.item-card').forEach(card => {
            const type     = card.querySelector('.inp-type').value;
            const id       = card.querySelector('.inp-item-id').value;
            const qty      = parseInt(card.querySelector('.inp-qty').value) || 0;
            const max      = parseInt(card.querySelector('.inp-qty').max);
            const noStock  = card.querySelector('.no-stock-warn');
            const noBatch  = card.querySelector('.no-batch-warn');
            const inpPr    = card.querySelector('.inp-price');
            const price    = parseFloat(inpPr?.value || 0);
            const priceMin = parseFloat(inpPr?.dataset.priceMin || 0);
            const priceMax = parseFloat(inpPr?.dataset.priceMax || 0);

            if ((noStock && noStock.style.display !== 'none' && noStock.style.display !== '') ||
                (noBatch && noBatch.style.display !== 'none' && noBatch.style.display !== '')) {
                errors.push('One or more items have no available purchase batch. Cannot complete this sale.');
                return;
            }
            if (!type || !id) {
                if (inpPr) { card.querySelector('.sel-type').style.borderColor = '#dc2626'; card.querySelector('.sel-item').style.borderColor = '#dc2626'; }
                errors.push('Please select a type and item for every row.');
                return;
            }
            if (!isNaN(max) && qty > max) {
                card.querySelector('.inp-qty').style.borderColor = '#dc2626';
                errors.push('Quantity exceeds available stock for one or more items.');
                return;
            }
            // Price range validation — show error modal, don't submit
            if (priceMin > 0 && price < priceMin) {
                inpPr.style.borderColor = '#dc2626';
                const selItem    = card.querySelector('.sel-item');
                const itemName   = selItem ? selItem.options[selItem.selectedIndex]?.text?.split(' — ')[0] : 'Item';
                errors.push('"' + itemName + '": price Br ' + price.toFixed(2)
                    + ' is below the minimum allowed price of Br ' + priceMin.toFixed(2) + '.'
                    + '\n\nPlease increase the price to at least Br ' + priceMin.toFixed(2) + ' before submitting.');
                return;
            }
            if (inpPr) inpPr.style.borderColor = '';

            const selBatch       = card.querySelector('.sel-batch');
            const batchOpt       = selBatch ? selBatch.options[selBatch.selectedIndex] : null;
            const batchUnitPrice = parseFloat(batchOpt?.dataset?.unitPrice || 0);
            const selItem        = card.querySelector('.sel-item');
            const resolvedName   = selItem ? selItem.options[selItem.selectedIndex]?.text?.split(' — ')[0] : 'Item';

            confirmLines.push({ name: resolvedName, qty, price, priceMin, priceMax, batchUnitPrice });
        });

        if (errors.length > 0) {
            showError(errors[0]);
            return null;
        }
        return confirmLines;
    }

    // ── Submit via AJAX ────────────────────────────────────────────
    function submitSaleAjax() {
        const form   = document.getElementById('saleForm');
        const btnOk  = document.getElementById('saleConfirmOk');

        // Re-enable ALL disabled inputs so FormData includes them
        form.querySelectorAll('input:disabled, select:disabled, textarea:disabled').forEach(function(el) {
            el.disabled = false;
        });

        // Fix TomSelect — it hides original selects; force sync values back
        form.querySelectorAll('.ts-select').forEach(function(sel) {
            if (sel._tomSelect) sel._tomSelect.sync();
        });

        const formData = new FormData(form);

        // Show loading state on OK button
        btnOk.disabled  = true;
        btnOk.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Processing…';

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                       || document.querySelector('input[name="_token"]')?.value || '';

        fetch(form.action, {
            method:  'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept':           'application/json',
                'X-CSRF-TOKEN':     csrfToken,
            },
            body: formData,
        })
        .then(function(response) {
            return response.json().then(function(data) {
                return { ok: response.ok, data: data };
            }).catch(function() {
                // Non-JSON response
                throw new Error('Unexpected server response. Please try again.');
            });
        })
        .then(function(result) {
            if (result.ok && result.data.redirect) {
                // Success — navigate to sale show page
                window.location.href = result.data.redirect;
            } else {
                // Server returned an error in JSON
                var msg = result.data.message || '';
                if (!msg && result.data.errors) {
                    var firstKey = Object.keys(result.data.errors)[0];
                    msg = result.data.errors[firstKey][0] || 'Validation failed.';
                }
                throw new Error(msg || 'Failed to save sale. Please check your input.');
            }
        })
        .catch(function(err) {
            // Close confirm modal, reset button, show error modal
            document.getElementById('saleConfirmModal').style.display = 'none';
            document.body.style.overflow = '';
            btnOk.disabled  = false;
            btnOk.innerHTML = '<i class="fa fa-check me-1"></i>Complete Sale';
            showError(err.message || 'An unexpected error occurred. Please try again.');
        });
    }

    document.getElementById('saleForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const lines = validateAndBuildLines();
        if (!lines) return; // validation error shown
        showSaleConfirm(lines, submitSaleAjax);
    });

    function esc(str) {
        return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // ── Sale confirmation modal ────────────────────────────────────
    function showSaleConfirm(items, onConfirm) {
        const modal      = document.getElementById('saleConfirmModal');
        const body       = document.getElementById('saleConfirmItems');
        const btnOk      = document.getElementById('saleConfirmOk');
        const btnCancel  = document.getElementById('saleConfirmCancel');

        // Build item cards
        body.innerHTML = items.map(function(item) {
            const priceRange = (item.priceMin > 0 || item.priceMax > 0)
                ? '<div style="color:#94a3b8;font-size:.72rem;margin-top:2px">range: Br '
                  + item.priceMin.toFixed(2) + ' – Br ' + item.priceMax.toFixed(2) + '</div>'
                : '';

            const profit     = item.batchUnitPrice > 0 ? (item.price - item.batchUnitPrice) * item.qty : null;
            const isProfit   = profit !== null && profit >= 0;
            const profitColor = profit === null ? '#94a3b8' : (isProfit ? '#16a34a' : '#dc2626');
            const profitLabel = profit === null ? '—'
                : (isProfit ? '+Br ' : '-Br ') + Math.abs(profit).toFixed(2);
            const profitText  = profit === null ? 'N/A' : (isProfit ? 'Profit' : 'Loss');

            return '<div style="background:#f8f9ff;border:1px solid #e2e6f0;border-radius:10px;padding:14px 16px">'
                + '<div style="font-weight:700;color:#1e293b;margin-bottom:10px;font-size:.95rem">'
                + '<i class="fa fa-gears me-2" style="color:#6366f1;font-size:.85rem"></i>' + item.name
                + '</div>'
                + '<div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px">'

                + '<div style="background:#fff;border:1px solid #e2e6f0;border-radius:8px;padding:10px;text-align:center">'
                + '<div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">Purchase</div>'
                + '<div style="font-weight:700;color:#475569;font-size:.9rem">'
                + (item.batchUnitPrice > 0 ? 'Br ' + item.batchUnitPrice.toFixed(2) : '—') + '</div>'
                + '</div>'

                + '<div style="background:#fff;border:1px solid #e2e6f0;border-radius:8px;padding:10px;text-align:center">'
                + '<div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">Sell Price</div>'
                + '<div style="font-weight:700;color:#6366f1;font-size:.9rem">Br ' + item.price.toFixed(2) + '</div>'
                + priceRange
                + '</div>'

                + '<div style="background:#fff;border:1px solid #e2e6f0;border-radius:8px;padding:10px;text-align:center">'
                + '<div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">Qty</div>'
                + '<div style="font-weight:700;color:#1e293b;font-size:.9rem">' + item.qty + '</div>'
                + '</div>'

                + '<div style="background:#fff;border:1px solid #e2e6f0;border-radius:8px;padding:10px;text-align:center">'
                + '<div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">' + profitText + '</div>'
                + '<div style="font-weight:700;font-size:.9rem;color:' + profitColor + '">' + profitLabel + '</div>'
                + '</div>'

                + '</div>'
                + '</div>';
        }).join('');

        // Show modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        function close() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            btnOk.removeEventListener('click', onOk);
            btnCancel.removeEventListener('click', close);
            modal.removeEventListener('click', onBackdrop);
        }
        function onOk() { close(); onConfirm(); }
        function onBackdrop(e) { if (e.target === modal) close(); }

        btnOk.addEventListener('click', onOk);
        btnCancel.addEventListener('click', close);
        modal.addEventListener('click', onBackdrop);
    }

    document.getElementById('addItemBtn').addEventListener('click', function () {
        const whId = getWarehouseId();
        if (!whId) { alert('Please select a warehouse first.'); return; }
        container.appendChild(createCard());
        checkSubmitBtn();
        recalcTotals();
    });

    document.getElementById('payFullBtn').addEventListener('click', function () {
        paidInput.value = totalInput.value;
        recalcTotals();
    });

    discountInput.addEventListener('input', recalcTotals);
    taxInput.addEventListener('input',      recalcTotals);
    paidInput.addEventListener('input',     recalcTotals);

    // Warehouse change
    (function () {
        const whSel = document.getElementById('warehouseSelect');
        if (!whSel) return;
        function onChange() {
            loadedWarehouse = null;
            const whId = whSel.value;
            if (!whId) return;
            loadWarehouseItems(whId, function () {
                container.querySelectorAll('.item-card').forEach(card => {
                    const type = card.querySelector('.inp-type').value;
                    if (type) onTypeSelected(card, type);
                    else clearBatchArea(card);
                });
                checkSubmitBtn();
                recalcTotals();
            });
        }
        whSel.addEventListener('change', onChange);
        setTimeout(function () { if (whSel._tomSelect) whSel._tomSelect.on('change', onChange); }, 500);
    })();

    // Init — add first card, load warehouse data
    container.appendChild(createCard());
    checkSubmitBtn();
    recalcTotals();
    const defaultWh = getWarehouseId();
    if (defaultWh) {
        loadWarehouseItems(defaultWh, function() {
            // If coming from a PO, auto-populate items
            if (PO_ITEMS && PO_ITEMS.length > 0) {
                // Remove the blank first card
                container.innerHTML = '';
                rowCount = 0;

                PO_ITEMS.forEach(function(poi) {
                    const card = createCard();
                    container.appendChild(card);

                    // Set type
                    const selType  = card.querySelector('.sel-type');
                    selType.value  = poi.item_type;
                    selType.dispatchEvent(new Event('change'));

                    // After type triggers item load, set item + batch
                    setTimeout(function() {
                        const selItem = card.querySelector('.sel-item');
                        if (selItem) {
                            selItem.value = String(poi.item_id);
                            selItem.dispatchEvent(new Event('change'));

                            // After batches load, select the specific purchase_item_id
                            setTimeout(function() {
                                const selBatch = card.querySelector('.sel-batch');
                                if (selBatch && poi.purchase_item_id) {
                                    for (let i = 0; i < selBatch.options.length; i++) {
                                        if (String(selBatch.options[i].value) === String(poi.purchase_item_id)) {
                                            selBatch.selectedIndex = i;
                                            selBatch.dispatchEvent(new Event('change'));
                                            break;
                                        }
                                    }
                                }
                                // Set qty to remaining
                                const inpQty = card.querySelector('.inp-qty');
                                if (inpQty && poi.remaining > 0) {
                                    inpQty.value = poi.remaining;
                                    inpQty.dispatchEvent(new Event('input'));
                                }
                            }, 600);
                        }
                    }, 400);
                });
                recalcTotals();
            }
        });
    }

})();
</script>
@endpush
