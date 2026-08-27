@extends('layouts.app')
@section('title','New Sale')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
    <li class="breadcrumb-item active">New Sale</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'New Sale','subtitle'=>'Invoice '.$invoice])

<form method="POST" action="{{ route('sales.store') }}" id="saleForm">
@csrf

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

    const WAREHOUSE_ITEMS_URL   = '{{ route("sales.ajax.warehouse-items") }}';
    const PURCHASE_BATCHES_URL  = '{{ route("sales.ajax.purchase-batches") }}';
    const DEFAULT_WAREHOUSE     = '{{ $defaultWarehouse?->id }}';

    let VEHICLES        = [];
    let CATEGORIES      = [];
    let loadedWarehouse = null;
    let rowCount        = 0;

    // DOM refs
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

    // ── Load warehouse items ───────────────────────────────────────
    function loadWarehouseItems(warehouseId, callback) {
        if (!warehouseId) { VEHICLES = []; CATEGORIES = []; if (callback) callback(); return; }
        if (loadedWarehouse === warehouseId) { if (callback) callback(); return; }

        fetch(WAREHOUSE_ITEMS_URL + '?warehouse_id=' + warehouseId)
            .then(r => r.json())
            .then(data => {
                VEHICLES        = data.vehicles   || [];
                CATEGORIES      = data.categories || [];
                loadedWarehouse = warehouseId;

                // Rebuild existing item dropdowns
                container.querySelectorAll('.item-card').forEach(card => {
                    const type    = card.querySelector('.inp-type').value;
                    const selItem = card.querySelector('.sel-item');
                    const curVal  = card.querySelector('.inp-item-id').value;
                    if (type) {
                        selItem.innerHTML = buildItemOptions(type);
                        selItem.disabled  = false;
                        if (curVal) selItem.value = curVal;
                        if (!selItem.value) {
                            card.querySelector('.inp-item-id').value = '';
                            card.querySelector('.inp-price').value   = '0.00';
                            clearBatchBlock(card);
                        }
                    }
                });

                if (callback) callback();
                recalcTotals();
            })
            .catch(() => { VEHICLES = []; CATEGORIES = []; });
    }

    // ── Build item <option> HTML ───────────────────────────────────
    function buildItemOptions(type) {
        let html = '<option value="">— Select item —</option>';
        if (type === 'vehicle') {
            if (!VEHICLES.length) return html + '<option disabled>No vehicles in warehouse</option>';
            VEHICLES.forEach(vt => {
                if (!vt.models.length) return;
                html += '<optgroup label="' + esc(vt.name) + '">';
                vt.models.forEach(m => {
                    html += '<option value="' + m.id + '" data-price="' + m.price + '" data-stock="' + m.stock + '" data-reorder="' + (m.reorder||2) + '">'
                         +  esc(m.name) + ' — Stock: ' + m.stock + '</option>';
                });
                html += '</optgroup>';
            });
        } else {
            if (!CATEGORIES.length) return html + '<option disabled>No spare parts in warehouse</option>';
            CATEGORIES.forEach(cat => {
                if (!cat.parts.length) return;
                html += '<optgroup label="' + esc(cat.name) + '">';
                cat.parts.forEach(p => {
                    html += '<option value="' + p.id + '" data-price="' + p.price + '" data-stock="' + p.stock + '" data-reorder="' + (p.reorder||5) + '">'
                         +  esc(p.name) + ' — Stock: ' + p.stock + (p.unit ? ' ' + p.unit : '') + '</option>';
                });
                html += '</optgroup>';
            });
        }
        return html;
    }

    // ── Load purchase batches for selected item ────────────────────
    function loadBatches(card, itemType, itemId) {
        const batchBlock = card.querySelector('.batch-block');
        const selBatch   = card.querySelector('.sel-batch');
        const batchInfo  = card.querySelector('.batch-info');

        batchBlock.style.display = 'none';
        selBatch.innerHTML = '<option value="">Loading...</option>';
        batchInfo.textContent = '';

        const whId = getWarehouseId();
        if (!itemType || !itemId || !whId) return;

        fetch(PURCHASE_BATCHES_URL + '?item_type=' + itemType + '&item_id=' + itemId + '&warehouse_id=' + whId)
            .then(r => r.json())
            .then(batches => {
                // No batches with remaining stock — hide the block entirely.
                // The sale will proceed using warehouse stock (FIFO auto).
                if (!batches.length) {
                    batchBlock.style.display = 'none';
                    return;
                }

                let html = '<option value="">— Any (FIFO auto) —</option>';
                batches.forEach(b => {
                    html += '<option value="' + b.purchase_item_id + '"'
                         +  ' data-remaining="' + b.remaining + '"'
                         +  ' data-unit-price="' + b.unit_price + '">'
                         +  esc(b.purchase_number) + ' — Remaining: ' + b.remaining
                         +  ' (bought @ Br ' + parseFloat(b.unit_price).toFixed(2) + ')'
                         +  '</option>';
                });
                selBatch.innerHTML = html;
                batchBlock.style.display = 'block';

                // Auto-select first batch
                selBatch.selectedIndex = 1;
                selBatch.dispatchEvent(new Event('change'));
            })
            .catch(() => {
                selBatch.innerHTML = '<option value="">Error loading batches</option>';
                batchBlock.style.display = 'block';
            });
    }

    function clearBatchBlock(card) {
        const bb = card.querySelector('.batch-block');
        if (bb) bb.style.display = 'none';
        const sb = card.querySelector('.sel-batch');
        if (sb) sb.innerHTML = '<option value="">—</option>';
        const bi = card.querySelector('.batch-info');
        if (bi) bi.textContent = '';
        const hi = card.querySelector('.inp-purchase-item-id');
        if (hi) hi.value = '';
    }

    // ── Create one item card ───────────────────────────────────────
    function createCard() {
        const idx = rowCount++;
        const div = document.createElement('div');
        div.className    = 'item-card';
        div.dataset.index = idx;

        div.innerHTML =
            // Hidden inputs
            '<input type="hidden" name="items[' + idx + '][item_type]"        class="inp-type"             value="">' +
            '<input type="hidden" name="items[' + idx + '][item_id]"          class="inp-item-id"          value="">' +
            '<input type="hidden" name="items[' + idx + '][total]"            class="inp-total"            value="0">' +
            '<input type="hidden" name="items[' + idx + '][purchase_item_id]" class="inp-purchase-item-id" value="">' +

            // Row number + remove button
            '<div class="item-num">Item #' + (idx + 1) + '</div>' +
            '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-card" title="Remove">' +
                '<i class="fa fa-times"></i>' +
            '</button>' +

            // Row 1: Type + Item
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

            // Row 2: PO# batch (hidden until item selected)
            '<div class="batch-block" style="display:none">' +
                '<div class="row g-2 mb-2">' +
                    '<div class="col-12">' +
                        '<label class="form-label small mb-1">' +
                            '<i class="fa fa-box me-1 text-primary"></i>Purchase Batch (PO#)' +
                        '</label>' +
                        '<select class="form-select form-select-sm sel-batch">' +
                            '<option value="">—</option>' +
                        '</select>' +
                        '<div class="batch-info"></div>' +
                    '</div>' +
                '</div>' +
            '</div>' +

            // Row 3: Price + Qty + Disc + Total
            '<div class="row g-2 align-items-end">' +
                '<div class="col-6 col-sm-3">' +
                    '<label class="form-label small mb-1">Price (Br)</label>' +
                    '<input type="number" name="items[' + idx + '][unit_price]"' +
                           ' class="form-control form-control-sm inp-price" value="0.00" min="0" step="0.01">' +
                '</div>' +
                '<div class="col-6 col-sm-2">' +
                    '<label class="form-label small mb-1">Qty</label>' +
                    '<input type="number" name="items[' + idx + '][quantity]"' +
                           ' class="form-control form-control-sm inp-qty" value="1" min="1">' +
                '</div>' +
                '<div class="col-6 col-sm-3">' +
                    '<label class="form-label small mb-1">Discount (Br)</label>' +
                    '<input type="number" name="items[' + idx + '][discount]"' +
                           ' class="form-control form-control-sm inp-disc" value="0.00" min="0" step="0.01">' +
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
        const inpType      = card.querySelector('.inp-type');
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
            const type  = this.value;
            const whId  = getWarehouseId();
            inpType.value   = type;
            inpItemId.value = '';
            inpPrice.value  = '0.00';
            stockWarn.classList.add('d-none');
            stockWarn.textContent = '';
            clearBatchBlock(card);

            if (!whId) {
                alert('Please select a warehouse first.');
                this.value    = '';
                inpType.value = '';
                return;
            }
            if (type) {
                loadWarehouseItems(whId, function () {
                    selItem.innerHTML = buildItemOptions(type);
                    selItem.disabled  = false;
                });
            } else {
                selItem.innerHTML = '<option value="">— Choose type first —</option>';
                selItem.disabled  = true;
            }
            updateRowTotal();
        });

        // Item changed
        selItem.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            inpItemId.value = this.value;
            stockWarn.classList.add('d-none');
            stockWarn.textContent = '';
            clearBatchBlock(card);

            if (this.value && opt.dataset.price !== undefined) {
                inpPrice.value = parseFloat(opt.dataset.price).toFixed(2);
                const stock   = parseInt(opt.dataset.stock)  || 0;
                const reorder = parseInt(opt.dataset.reorder) || 0;

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

                // Load purchase batches for this item
                loadBatches(card, inpType.value, this.value);
            } else {
                inpPrice.value = '0.00';
                inpQty.removeAttribute('max');
            }
            updateRowTotal();
        });

        // Batch selected
        selBatch.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            inpPurchItem.value = this.value;

            if (this.value && opt.dataset.remaining !== undefined) {
                const remaining = parseInt(opt.dataset.remaining);
                // Lock qty max to this batch's remaining
                inpQty.max = remaining;
                if (parseInt(inpQty.value) > remaining) inpQty.value = remaining;

                batchInfo.innerHTML =
                    '<i class="fa fa-circle-check me-1 text-success"></i>' +
                    'Remaining in this batch: <strong>' + remaining + '</strong> units';
            } else {
                // "Any (FIFO auto)" — unlock and use warehouse stock max
                inpPurchItem.value = '';
                const whMax = parseInt(selItem.options[selItem.selectedIndex]?.dataset?.stock) || 0;
                if (whMax) {
                    inpQty.max = whMax;
                    if (parseInt(inpQty.value) > whMax) inpQty.value = whMax;
                } else {
                    inpQty.removeAttribute('max');
                }
                batchInfo.innerHTML = '<span class="text-muted">FIFO: oldest batch will be assigned automatically</span>';
            }
            updateRowTotal();
        });

        // Qty input
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
            } else {
                alert('At least one item is required.');
            }
        });
    }

    // ── Renumber item cards after removal ─────────────────────────
    function renumberCards() {
        container.querySelectorAll('.item-card .item-num').forEach((el, i) => {
            el.textContent = 'Item #' + (i + 1);
        });
    }

    // ── Grand total recalc ─────────────────────────────────────────
    function recalcTotals() {
        let subtotal = 0;
        container.querySelectorAll('.inp-total').forEach(el => {
            subtotal += parseFloat(el.value) || 0;
        });
        const discount = Math.max(0, parseFloat(discountInput.value) || 0);
        const taxRate  = Math.max(0, parseFloat(taxInput.value)      || 0);
        const taxAmt   = ((subtotal - discount) * taxRate) / 100;
        const total    = Math.max(0, subtotal - discount + taxAmt);
        const paid     = Math.max(0, parseFloat(paidInput.value) || 0);
        const balance  = Math.max(0, total - paid);

        subtotalEl.textContent = subtotal.toFixed(2);
        totalEl.textContent    = total.toFixed(2);
        balanceEl.textContent  = balance.toFixed(2);

        subtotalInput.value = subtotal.toFixed(2);
        taxAmtInput.value   = taxAmt.toFixed(2);
        totalInput.value    = total.toFixed(2);
        balanceInput.value  = balance.toFixed(2);
    }

    // ── Form submit validation ─────────────────────────────────────
    document.getElementById('saleForm').addEventListener('submit', function (e) {
        let valid  = true;
        let errors = [];

        container.querySelectorAll('.item-card').forEach(card => {
            const type = card.querySelector('.inp-type').value;
            const id   = card.querySelector('.inp-item-id').value;
            const qty  = parseInt(card.querySelector('.inp-qty').value) || 0;
            const max  = parseInt(card.querySelector('.inp-qty').max);

            if (!type || !id) {
                valid = false;
                card.querySelector('.sel-type').style.borderColor = '#dc2626';
                card.querySelector('.sel-item').style.borderColor = '#dc2626';
                errors.push('Please select a type and item for every row.');
            } else if (!isNaN(max) && qty > max) {
                valid = false;
                card.querySelector('.inp-qty').style.borderColor = '#dc2626';
                errors.push('Quantity exceeds available stock for one or more items.');
            }
        });

        if (!valid) { e.preventDefault(); alert(errors[0]); }
    });

    // ── Helpers ───────────────────────────────────────────────────
    function esc(str) {
        return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // ── Wire up buttons ────────────────────────────────────────────
    document.getElementById('addItemBtn').addEventListener('click', function () {
        const whId = getWarehouseId();
        if (!whId) { alert('Please select a warehouse first.'); return; }
        container.appendChild(createCard());
        recalcTotals();
    });

    document.getElementById('payFullBtn').addEventListener('click', function () {
        paidInput.value = totalInput.value;
        recalcTotals();
    });

    discountInput.addEventListener('input', recalcTotals);
    taxInput.addEventListener('input',      recalcTotals);
    paidInput.addEventListener('input',     recalcTotals);

    // Warehouse change → reload items
    (function bindWarehouseChange() {
        const whSel = document.getElementById('warehouseSelect');
        if (!whSel) return;

        function onWarehouseChange() {
            loadedWarehouse = null;
            const whId = whSel.value;
            if (!whId) return;
            loadWarehouseItems(whId, function () {
                container.querySelectorAll('.item-card').forEach(card => {
                    const type = card.querySelector('.inp-type').value;
                    if (type) {
                        card.querySelector('.sel-item').innerHTML = buildItemOptions(type);
                        card.querySelector('.inp-item-id').value  = '';
                        card.querySelector('.inp-price').value    = '0.00';
                        clearBatchBlock(card);
                    }
                });
                recalcTotals();
            });
        }

        whSel.addEventListener('change', onWarehouseChange);
        // Also works with TomSelect
        setTimeout(function () {
            if (whSel._tomSelect) whSel._tomSelect.on('change', onWarehouseChange);
        }, 500);
    })();

    // ── Init: first card + load default warehouse ──────────────────
    container.appendChild(createCard());
    recalcTotals();
    const defaultWh = getWarehouseId();
    if (defaultWh) loadWarehouseItems(defaultWh, null);

})();
</script>
@endpush
