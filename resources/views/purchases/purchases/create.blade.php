@extends('layouts.app')
@section('title','New Purchase')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
    <li class="breadcrumb-item active">New</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'New Purchase Order','subtitle'=>$number])

<form method="POST" action="{{ route('purchases.store') }}" id="purchaseForm">
@csrf
<input type="hidden" name="subtotal" id="subtotalInput" value="0">
<input type="hidden" name="tax"      id="taxAmountInput" value="0">
<input type="hidden" name="total"    id="totalInput" value="0">
<input type="hidden" name="balance"  id="balanceInput" value="0">

<div class="row g-3">

{{-- LEFT --}}
<div class="col-12 col-lg-8">

<div class="card mb-3">
<div class="card-header"><i class="fa fa-file-invoice me-2" style="color:var(--brand-1)"></i>Purchase Information</div>
<div class="card-body">
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">PO Number</label>
        <input type="text" class="form-control" value="{{ $number }}" readonly>
    </div>
    <div class="col-md-4">
        <label class="form-label">Supplier <span class="text-danger">*</span></label>
        <select name="supplier_id" class="form-select ts-select @error('supplier_id') is-invalid @enderror" required>
            <option value="">Select supplier...</option>
            @foreach($suppliers as $s)
                <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>
                    {{ $s->name }}
                </option>
            @endforeach
        </select>
        @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Purchase Date <span class="text-danger">*</span></label>
        <input type="date" name="purchase_date" class="form-control"
               value="{{ old('purchase_date', today()->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Due Date</label>
        <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
    </div>
    @if($warehouses->count() > 1)
    <div class="col-md-4">
        <label class="form-label">Warehouse <span class="text-danger">*</span></label>
        <select name="warehouse_id" class="form-select ts-select" required>
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}" {{ $defaultWarehouse?->id == $wh->id ? 'selected' : '' }}>
                    {{ $wh->name }}{{ $wh->city ? ' ('.$wh->city.')' : '' }}
                </option>
            @endforeach
        </select>
    </div>
    @else
        <input type="hidden" name="warehouse_id" value="{{ $defaultWarehouse?->id }}">
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
    <span><i class="fa fa-list me-2" style="color:var(--brand-1)"></i>Purchase Items</span>
    <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">
        <i class="fa fa-plus me-1"></i>Add Item
    </button>
</div>
<div class="card-body p-2" id="itemsContainer">
    {{-- Cards injected by JS --}}
</div>
</div>

</div>{{-- /col-8 --}}

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
        <span class="text-muted">Balance Owed</span>
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
        <i class="fa fa-save me-1"></i>Save Purchase
    </button>
    <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
</div>
</div>

</div>{{-- /col-4 --}}
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
</style>

@endsection
@push('scripts')
<script>
(function () {
    'use strict';

    const VEHICLES   = {!! $vehicleTypesJson !!};
    const CATEGORIES = {!! $categoriesJson !!};

    let rowCount = 0;

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

    // ── Build item options (excluding already-selected items in other cards) ──
    // excludeCard: the card being built for (its own selection is NOT excluded)
    function buildOptionsHtml(type, excludeCard) {
        // Collect item IDs already claimed by OTHER cards of the same type
        const taken = new Set();
        container.querySelectorAll('.item-card').forEach(function(c) {
            if (c === excludeCard) return;
            if (c.querySelector('.inp-type').value === type) {
                const id = c.querySelector('.inp-item-id').value;
                if (id) taken.add(String(id));
            }
        });

        let html = '<option value="">— Select item —</option>';
        if (type === 'vehicle') {
            if (!VEHICLES.length) return html + '<option disabled>No vehicles available</option>';
            VEHICLES.forEach(vt => {
                if (!vt.models.length) return;
                const opts = vt.models.map(m => {
                    const disabled = taken.has(String(m.id)) ? ' disabled' : '';
                    const label    = taken.has(String(m.id)) ? ' (already in another row)' : '';
                    return '<option value="' + m.id + '" data-price="' + m.price + '"' + disabled + '>'
                         + esc(m.name) + ' — Unsold: ' + m.unsold + label + '</option>';
                }).join('');
                html += '<optgroup label="' + esc(vt.name) + '">' + opts + '</optgroup>';
            });
        } else {
            if (!CATEGORIES.length) return html + '<option disabled>No spare parts available</option>';
            CATEGORIES.forEach(cat => {
                if (!cat.parts.length) return;
                const opts = cat.parts.map(p => {
                    const disabled = taken.has(String(p.id)) ? ' disabled' : '';
                    const label    = taken.has(String(p.id)) ? ' (already in another row)' : '';
                    return '<option value="' + p.id + '" data-price="' + p.price + '"' + disabled + '>'
                         + esc(p.name) + ' — Unsold: ' + p.unsold + (p.unit ? ' ' + p.unit : '') + label + '</option>';
                }).join('');
                html += '<optgroup label="' + esc(cat.name) + '">' + opts + '</optgroup>';
            });
        }
        return html;
    }

    // ── Rebuild item dropdowns across all cards that share the same type ──
    // Called after any item selection change or card removal so every card
    // instantly reflects what its siblings have already claimed.
    function syncItemOptions() {
        container.querySelectorAll('.item-card').forEach(function(card) {
            const type    = card.querySelector('.inp-type').value;
            const selItem = card.querySelector('.sel-item');
            const curVal  = card.querySelector('.inp-item-id').value;
            if (!type || selItem.disabled) return;

            // Rebuild options, passing this card so its own selection isn't excluded
            selItem.innerHTML = buildOptionsHtml(type, card);

            // Restore the previously selected value for this card
            if (curVal) {
                for (let i = 0; i < selItem.options.length; i++) {
                    if (selItem.options[i].value === curVal) {
                        selItem.selectedIndex = i;
                        break;
                    }
                }
            }
        });
    }

    // ── Create one item card ───────────────────────────────────────
    function createCard() {
        const idx = rowCount++;
        const div = document.createElement('div');
        div.className     = 'item-card';
        div.dataset.index = idx;

        div.innerHTML =
            // Hidden inputs
            '<input type="hidden" name="items[' + idx + '][item_type]" class="inp-type"    value="">' +
            '<input type="hidden" name="items[' + idx + '][item_id]"   class="inp-item-id" value="">' +
            '<input type="hidden" name="items[' + idx + '][total]"     class="inp-total"   value="0">' +

            // Row number + remove button
            '<div class="item-num">Item #' + (idx + 1) + '</div>' +
            '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-card" title="Remove">' +
                '<i class="fa fa-times"></i>' +
            '</button>' +

            // Row 1: Type + Item (full width)
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
                '</div>' +
            '</div>' +

            // Row 2: Cost + Qty + Disc + Total
            '<div class="row g-2 align-items-end">' +
                '<div class="col-6 col-sm-3">' +
                    '<label class="form-label small mb-1">Cost (Br)</label>' +
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

    // ── Bind events ────────────────────────────────────────────────
    function bindCard(card) {
        const selType   = card.querySelector('.sel-type');
        const selItem   = card.querySelector('.sel-item');
        const inpType   = card.querySelector('.inp-type');
        const inpItemId = card.querySelector('.inp-item-id');
        const inpPrice  = card.querySelector('.inp-price');
        const inpQty    = card.querySelector('.inp-qty');
        const inpDisc   = card.querySelector('.inp-disc');
        const inpTotal  = card.querySelector('.inp-total');
        const lblTotal  = card.querySelector('.lbl-total');
        const btnRemove = card.querySelector('.btn-remove-card');

        function updateRowTotal() {
            const qty   = Math.max(0, parseFloat(inpQty.value)   || 0);
            const price = Math.max(0, parseFloat(inpPrice.value) || 0);
            const disc  = Math.max(0, parseFloat(inpDisc.value)  || 0);
            const total = Math.max(0, (qty * price) - disc);
            lblTotal.textContent = 'Br ' + total.toFixed(2);
            inpTotal.value       = total.toFixed(2);
            recalcTotals();
        }

        selType.addEventListener('change', function () {
            const type    = this.value;
            inpType.value   = type;
            inpItemId.value = '';
            inpPrice.value  = '0.00';
            if (type) {
                selItem.innerHTML = buildOptionsHtml(type, card);
                selItem.disabled  = false;
            } else {
                selItem.innerHTML = '<option value="">— Choose type first —</option>';
                selItem.disabled  = true;
            }
            // Sync sibling cards so they reflect this card's type change
            syncItemOptions();
            updateRowTotal();
        });

        selItem.addEventListener('change', function () {
            inpItemId.value = this.value;
            const opt = this.options[this.selectedIndex];
            inpPrice.value = this.value ? parseFloat(opt.dataset.price || 0).toFixed(2) : '0.00';
            // Sync sibling cards so they disable this item in their own dropdowns
            syncItemOptions();
            updateRowTotal();
        });

        [inpPrice, inpQty, inpDisc].forEach(el => el.addEventListener('input', updateRowTotal));

        btnRemove.addEventListener('click', function () {
            if (container.querySelectorAll('.item-card').length > 1) {
                card.remove();
                renumberCards();
                // Free up this card's item so siblings can select it again
                syncItemOptions();
                recalcTotals();
            } else {
                alert('At least one item is required.');
            }
        });
    }

    // ── Renumber after removal ─────────────────────────────────────
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
        subtotalInput.value    = subtotal.toFixed(2);
        taxAmtInput.value      = taxAmt.toFixed(2);
        totalInput.value       = total.toFixed(2);
        balanceInput.value     = balance.toFixed(2);
    }

    // ── Submit validation ──────────────────────────────────────────
    document.getElementById('purchaseForm').addEventListener('submit', function (e) {
        let valid  = true;
        let errors = [];

        // Cross-row duplicate check
        const seen = {}; // "type:id" → row index
        container.querySelectorAll('.item-card').forEach((card, idx) => {
            const type = card.querySelector('.inp-type').value;
            const id   = card.querySelector('.inp-item-id').value;
            if (!type || !id) {
                valid = false;
                card.querySelector('.sel-type').style.borderColor = '#dc2626';
                card.querySelector('.sel-item').style.borderColor = '#dc2626';
                errors.push('Please select a type and item for every row.');
                return;
            }
            const key = type + ':' + id;
            if (seen[key] !== undefined) {
                valid = false;
                card.querySelector('.sel-item').style.borderColor = '#dc2626';
                errors.push('Item in row #' + (idx + 1) + ' is already added in row #' + (seen[key] + 1) + '. Each item can only appear once — increase the quantity instead.');
            } else {
                seen[key] = idx;
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

    // ── Init ───────────────────────────────────────────────────────
    container.appendChild(createCard());
    recalcTotals();

})();
</script>
@endpush
