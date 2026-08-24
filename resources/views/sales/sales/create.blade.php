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

{{-- Hidden totals (filled by JS before submit) --}}
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
        <select name="customer_id" class="form-select">
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
        <select name="payment_method" class="form-select" required>
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="cheque">Cheque</option>
            <option value="credit">Credit</option>
        </select>
    </div>
    @if($warehouses->count() > 1)
    <div class="col-md-6">
        <label class="form-label">Warehouse / Stock Location <span class="text-danger">*</span></label>
        <select name="warehouse_id" class="form-select" required>
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}" {{ $defaultWarehouse?->id == $wh->id ? 'selected' : '' }}>
                    <i class="fa fa-warehouse"></i> {{ $wh->name }} ({{ $wh->city ?? $wh->code }})
                </option>
            @endforeach
        </select>
    </div>
    @else
        <input type="hidden" name="warehouse_id" value="{{ $defaultWarehouse?->id }}">
    @endif
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="1" placeholder="Optional notes…"></textarea>
    </div>
</div>
</div>
</div>

{{-- Items Table --}}
<div class="card">
<div class="card-header d-flex align-items-center justify-content-between">
    <span><i class="fa fa-list me-2" style="color:var(--brand-1)"></i>Sale Items</span>
    <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">
        <i class="fa fa-plus me-1"></i>Add Item
    </button>
</div>
<div class="table-responsive">
<table class="table mb-0" id="itemsTable">
    <thead>
        <tr>
            <th style="width:120px">Type</th>
            <th>Item</th>
            <th style="width:120px">Price (Br)</th>
            <th style="width:80px">Qty</th>
            <th style="width:100px">Disc (Br)</th>
            <th style="width:100px">Total (Br)</th>
            <th style="width:40px"></th>
        </tr>
    </thead>
    <tbody id="itemsContainer">
        {{-- Rows injected by JS only --}}
    </tbody>
</table>
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

@push('scripts')
<script>
(function() {
    'use strict';

    // ── Data from server ────────────────────────────
    const VEHICLES   = {!! $vehicleTypesJson !!};
    const CATEGORIES = {!! $categoriesJson !!};

    let rowCount = 0; // tracks total rows ever created (for unique index)

    // ── DOM refs ────────────────────────────────────
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

    // ── Build item options HTML ─────────────────────
    function buildOptionsHtml(type) {
        let html = '<option value="">— Select item —</option>';
        if (type === 'vehicle') {
            VEHICLES.forEach(vt => {
                if (!vt.models.length) return;
                html += `<optgroup label="${vt.name}">`;
                vt.models.forEach(m => {
                    html += `<option value="${m.id}" data-price="${m.price}" data-stock="${m.stock}">
                        ${m.name} (Stock: ${m.stock})
                    </option>`;
                });
                html += '</optgroup>';
            });
        } else if (type === 'spare_part') {
            CATEGORIES.forEach(cat => {
                if (!cat.parts.length) return;
                html += `<optgroup label="${cat.name}">`;
                cat.parts.forEach(p => {
                    html += `<option value="${p.id}" data-price="${p.price}" data-stock="${p.stock}">
                        ${p.name} — Stock: ${p.stock} ${p.unit || ''}
                    </option>`;
                });
                html += '</optgroup>';
            });
        }
        return html;
    }

    // ── Create a brand new row ──────────────────────
    function createRow() {
        const idx = rowCount++;
        const tr  = document.createElement('tr');
        tr.className = 'item-row';
        tr.dataset.index = idx;

        tr.innerHTML = `
            <td>
                <input type="hidden" name="items[${idx}][item_type]"  class="inp-type"    value="">
                <input type="hidden" name="items[${idx}][item_id]"    class="inp-item-id" value="">
                <input type="hidden" name="items[${idx}][total]"      class="inp-total"   value="0">
                <select class="form-select form-select-sm sel-type">
                    <option value="">Select…</option>
                    <option value="spare_part">Spare Part</option>
                    <option value="vehicle">Vehicle</option>
                </select>
            </td>
            <td>
                <select class="form-select form-select-sm sel-item" disabled>
                    <option value="">— Choose type first —</option>
                </select>
            </td>
            <td>
                <input type="number" name="items[${idx}][unit_price]"
                       class="form-control form-control-sm inp-price"
                       value="0.00" min="0" step="0.01">
            </td>
            <td>
                <input type="number" name="items[${idx}][quantity]"
                       class="form-control form-control-sm inp-qty"
                       value="1" min="1">
            </td>
            <td>
                <input type="number" name="items[${idx}][discount]"
                       class="form-control form-control-sm inp-disc"
                       value="0.00" min="0" step="0.01">
            </td>
            <td class="fw-semibold lbl-total" style="white-space:nowrap">0.00</td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove"
                        title="Remove row" style="padding:3px 8px">
                    <i class="fa fa-times"></i>
                </button>
            </td>
        `;

        bindRow(tr);
        return tr;
    }

    // ── Bind events to a row ────────────────────────
    function bindRow(tr) {
        const selType   = tr.querySelector('.sel-type');
        const selItem   = tr.querySelector('.sel-item');
        const inpType   = tr.querySelector('.inp-type');
        const inpItemId = tr.querySelector('.inp-item-id');
        const inpPrice  = tr.querySelector('.inp-price');
        const inpQty    = tr.querySelector('.inp-qty');
        const inpDisc   = tr.querySelector('.inp-disc');
        const inpTotal  = tr.querySelector('.inp-total');
        const lblTotal  = tr.querySelector('.lbl-total');
        const btnRemove = tr.querySelector('.btn-remove');

        function updateRowTotal() {
            const qty   = Math.max(0, parseFloat(inpQty.value)   || 0);
            const price = Math.max(0, parseFloat(inpPrice.value) || 0);
            const disc  = Math.max(0, parseFloat(inpDisc.value)  || 0);
            const total = Math.max(0, (qty * price) - disc);
            lblTotal.textContent   = total.toFixed(2);
            inpTotal.value         = total.toFixed(2);
            recalcTotals();
        }

        // Type changed → populate item dropdown
        selType.addEventListener('change', function() {
            const type = this.value;
            inpType.value     = type;
            inpItemId.value   = '';
            inpPrice.value    = '0.00';

            if (type) {
                selItem.innerHTML = buildOptionsHtml(type);
                selItem.disabled  = false;
            } else {
                selItem.innerHTML = '<option value="">— Choose type first —</option>';
                selItem.disabled  = true;
            }
            updateRowTotal();
        });

        // Item changed → fill price
        selItem.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            inpItemId.value = this.value;
            if (this.value && opt.dataset.price) {
                inpPrice.value = parseFloat(opt.dataset.price).toFixed(2);
            } else {
                inpPrice.value = '0.00';
            }
            updateRowTotal();
        });

        inpPrice.addEventListener('input', updateRowTotal);
        inpQty.addEventListener('input',   updateRowTotal);
        inpDisc.addEventListener('input',  updateRowTotal);

        btnRemove.addEventListener('click', function() {
            if (container.querySelectorAll('.item-row').length > 1) {
                tr.remove();
                recalcTotals();
            } else {
                alert('At least one item is required.');
            }
        });
    }

    // ── Recalculate grand totals ────────────────────
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

        subtotalEl.textContent   = subtotal.toFixed(2);
        totalEl.textContent      = total.toFixed(2);
        balanceEl.textContent    = balance.toFixed(2);

        subtotalInput.value  = subtotal.toFixed(2);
        taxAmtInput.value    = taxAmt.toFixed(2);
        totalInput.value     = total.toFixed(2);
        balanceInput.value   = balance.toFixed(2);
    }

    // ── Validate before submit ──────────────────────
    document.getElementById('saleForm').addEventListener('submit', function(e) {
        const rows = container.querySelectorAll('.item-row');
        let valid  = true;

        rows.forEach(row => {
            const type = row.querySelector('.inp-type').value;
            const id   = row.querySelector('.inp-item-id').value;
            if (!type || !id) {
                valid = false;
                row.querySelector('.sel-type').style.borderColor = '#dc2626';
                row.querySelector('.sel-item').style.borderColor = '#dc2626';
            }
        });

        if (!valid) {
            e.preventDefault();
            alert('Please select a type and item for every row before submitting.');
        }
    });

    // ── Add Item button ─────────────────────────────
    document.getElementById('addItemBtn').addEventListener('click', function() {
        container.appendChild(createRow());
        recalcTotals();
    });

    // ── Pay Full button ─────────────────────────────
    document.getElementById('payFullBtn').addEventListener('click', function() {
        paidInput.value = totalInput.value;
        recalcTotals();
    });

    // ── Discount / Tax / Paid inputs ────────────────
    discountInput.addEventListener('input', recalcTotals);
    taxInput.addEventListener('input',      recalcTotals);
    paidInput.addEventListener('input',     recalcTotals);

    // ── Init: add first row on page load ────────────
    container.appendChild(createRow());
    recalcTotals();

})();
</script>
@endpush
@endsection
