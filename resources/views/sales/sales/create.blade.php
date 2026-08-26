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
        <label class="form-label">Warehouse / Stock Location <span class="text-danger">*</span></label>
        <select name="warehouse_id" id="warehouseSelect" class="form-select ts-select" required>
            <option value="">-- Select Warehouse --</option>
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}"
                    data-city="{{ $wh->city ?? '' }}"
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

@endsection
@push('scripts')
<script>
(function() {
    'use strict';

    // AJAX URL to load warehouse-specific items
    const WAREHOUSE_ITEMS_URL = '{{ route("sales.ajax.warehouse-items") }}';
    const DEFAULT_WAREHOUSE   = '{{ $defaultWarehouse?->id }}';

    // Current items data (loaded per warehouse)
    let VEHICLES   = [];
    let CATEGORIES = [];
    let loadedWarehouse = null;

    let rowCount = 0;

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

    // Get current warehouse ID
    function getWarehouseId() {
        const sel = document.getElementById('warehouseSelect');
        if (!sel) return DEFAULT_WAREHOUSE;
        // TomSelect stores value on the original select element
        return sel.value || DEFAULT_WAREHOUSE;
    }

    // Load items for selected warehouse via AJAX
    function loadWarehouseItems(warehouseId, callback) {
        if (!warehouseId) {
            VEHICLES = []; CATEGORIES = [];
            if (callback) callback();
            return;
        }
        if (loadedWarehouse === warehouseId) {
            if (callback) callback();
            return;
        }

        // Show loading indicator on all item selects
        container.querySelectorAll('.sel-item').forEach(function(sel) {
            sel.innerHTML = '<option value="">Loading...</option>';
        });

        fetch(WAREHOUSE_ITEMS_URL + '?warehouse_id=' + warehouseId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                VEHICLES        = data.vehicles   || [];
                CATEGORIES      = data.categories || [];
                loadedWarehouse = warehouseId;

                // Rebuild all existing item dropdowns
                container.querySelectorAll('.item-row').forEach(function(row) {
                    const type     = row.querySelector('.inp-type').value;
                    const selItem  = row.querySelector('.sel-item');
                    const curVal   = row.querySelector('.inp-item-id').value;
                    if (type) {
                        selItem.innerHTML = buildOptionsHtml(type);
                        selItem.disabled  = false;
                        // Try to restore previous selection
                        if (curVal) selItem.value = curVal;
                        // If item no longer in warehouse, reset
                        if (!selItem.value) {
                            row.querySelector('.inp-item-id').value = '';
                            row.querySelector('.inp-price').value   = '0.00';
                        }
                    }
                });

                if (callback) callback();
                recalcTotals();
            })
            .catch(function() {
                VEHICLES = []; CATEGORIES = [];
                container.querySelectorAll('.sel-item').forEach(function(sel) {
                    sel.innerHTML = '<option value="">Error loading items</option>';
                });
            });
    }

    // Build item options HTML from loaded data
    function buildOptionsHtml(type) {
        let html = '<option value="">- Select item -</option>';
        if (type === 'vehicle') {
            if (!VEHICLES.length) {
                html += '<option value="" disabled>No vehicles in this warehouse</option>';
                return html;
            }
            VEHICLES.forEach(function(vt) {
                if (!vt.models.length) return;
                html += '<optgroup label="' + vt.name + '">';
                vt.models.forEach(function(m) {
                    html += '<option value="' + m.id + '" data-price="' + m.price + '" data-stock="' + m.stock + '">'
                          + m.name + ' - Stock: ' + m.stock
                          + '</option>';
                });
                html += '</optgroup>';
            });
        } else if (type === 'spare_part') {
            if (!CATEGORIES.length) {
                html += '<option value="" disabled>No spare parts in this warehouse</option>';
                return html;
            }
            CATEGORIES.forEach(function(cat) {
                if (!cat.parts.length) return;
                html += '<optgroup label="' + cat.name + '">';
                cat.parts.forEach(function(p) {
                    html += '<option value="' + p.id + '" data-price="' + p.price + '" data-stock="' + p.stock + '">'
                          + p.name + ' - Stock: ' + p.stock + ' ' + (p.unit || '')
                          + '</option>';
                });
                html += '</optgroup>';
            });
        }
        return html;
    }

    // Create a new item row
    function createRow() {
        const idx = rowCount++;
        const tr  = document.createElement('tr');
        tr.className    = 'item-row';
        tr.dataset.index = idx;

        tr.innerHTML =
            '<td>' +
                '<input type="hidden" name="items[' + idx + '][item_type]"  class="inp-type"    value="">' +
                '<input type="hidden" name="items[' + idx + '][item_id]"    class="inp-item-id" value="">' +
                '<input type="hidden" name="items[' + idx + '][total]"      class="inp-total"   value="0">' +
                '<select class="form-select form-select-sm sel-type">' +
                    '<option value="">Select...</option>' +
                    '<option value="spare_part">Spare Part</option>' +
                    '<option value="vehicle">Vehicle</option>' +
                '</select>' +
            '</td>' +
            '<td>' +
                '<select class="form-select form-select-sm sel-item" disabled>' +
                    '<option value="">- Choose type first -</option>' +
                '</select>' +
            '</td>' +
            '<td>' +
                '<input type="number" name="items[' + idx + '][unit_price]"' +
                       ' class="form-control form-control-sm inp-price"' +
                       ' value="0.00" min="0" step="0.01">' +
            '</td>' +
            '<td>' +
                '<input type="number" name="items[' + idx + '][quantity]"' +
                       ' class="form-control form-control-sm inp-qty"' +
                       ' value="1" min="1">' +
            '</td>' +
            '<td>' +
                '<input type="number" name="items[' + idx + '][discount]"' +
                       ' class="form-control form-control-sm inp-disc"' +
                       ' value="0.00" min="0" step="0.01">' +
            '</td>' +
            '<td class="fw-semibold lbl-total" style="white-space:nowrap">0.00</td>' +
            '<td>' +
                '<button type="button" class="btn btn-sm btn-outline-danger btn-remove"' +
                        ' title="Remove row" style="padding:3px 8px">' +
                    '<i class="fa fa-times"></i>' +
                '</button>' +
            '</td>';

        bindRow(tr);
        return tr;
    }

    // Bind events to a row
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
            lblTotal.textContent = total.toFixed(2);
            inpTotal.value       = total.toFixed(2);
            recalcTotals();
        }

        // Type changed -> populate item dropdown from warehouse data
        selType.addEventListener('change', function() {
            const type    = this.value;
            const whId    = getWarehouseId();
            inpType.value   = type;
            inpItemId.value = '';
            inpPrice.value  = '0.00';

            if (!whId) {
                alert('Please select a warehouse first.');
                this.value    = '';
                inpType.value = '';
                return;
            }

            if (type) {
                loadWarehouseItems(whId, function() {
                    selItem.innerHTML = buildOptionsHtml(type);
                    selItem.disabled  = false;
                });
            } else {
                selItem.innerHTML = '<option value="">- Choose type first -</option>';
                selItem.disabled  = true;
            }
            updateRowTotal();
        });

        // Item changed -> fill price, set max qty, show stock warning
        selItem.addEventListener('change', function() {
            const opt   = this.options[this.selectedIndex];
            inpItemId.value = this.value;
            // Remove old warning
            var existingWarn = tr.querySelector('.stock-warn');
            if (existingWarn) existingWarn.remove();

            if (this.value && opt.dataset.price) {
                inpPrice.value = parseFloat(opt.dataset.price).toFixed(2);
                var stock = parseInt(opt.dataset.stock) || 0;

                // Enforce max qty = available stock
                inpQty.max = stock;
                if (parseInt(inpQty.value) > stock) {
                    inpQty.value = stock;
                }

                // Stock warning
                if (stock <= 0) {
                    var warn = document.createElement('small');
                    warn.className = 'stock-warn text-danger d-block mt-1';
                    warn.innerHTML = '<i class="fa fa-circle-xmark me-1"></i>Out of stock in this warehouse';
                    selItem.parentNode.appendChild(warn);
                } else if (stock <= 5) {
                    var warn = document.createElement('small');
                    warn.className = 'stock-warn text-warning d-block mt-1';
                    warn.innerHTML = '<i class="fa fa-triangle-exclamation me-1"></i>Only ' + stock + ' left in warehouse';
                    selItem.parentNode.appendChild(warn);
                }
            } else {
                inpPrice.value = '0.00';
                inpQty.removeAttribute('max');
            }
            updateRowTotal();
        });

        // Qty input: enforce max stock
        inpQty.addEventListener('input', function() {
            var maxStock = parseInt(this.max);
            if (!isNaN(maxStock) && parseInt(this.value) > maxStock) {
                this.value = maxStock;
                // Flash red border briefly
                this.style.borderColor = '#dc2626';
                setTimeout(function() { inpQty.style.borderColor = ''; }, 1500);
            }
            updateRowTotal();
        });

        btnRemove.addEventListener('click', function() {
            if (container.querySelectorAll('.item-row').length > 1) {
                tr.remove();
                recalcTotals();
            } else {
                alert('At least one item is required.');
            }
        });
    }

    // Recalculate grand totals
    function recalcTotals() {
        var subtotal = 0;
        container.querySelectorAll('.inp-total').forEach(function(el) {
            subtotal += parseFloat(el.value) || 0;
        });
        var discount = Math.max(0, parseFloat(discountInput.value) || 0);
        var taxRate  = Math.max(0, parseFloat(taxInput.value)      || 0);
        var taxAmt   = ((subtotal - discount) * taxRate) / 100;
        var total    = Math.max(0, subtotal - discount + taxAmt);
        var paid     = Math.max(0, parseFloat(paidInput.value) || 0);
        var balance  = Math.max(0, total - paid);

        subtotalEl.textContent = subtotal.toFixed(2);
        totalEl.textContent    = total.toFixed(2);
        balanceEl.textContent  = balance.toFixed(2);

        subtotalInput.value = subtotal.toFixed(2);
        taxAmtInput.value   = taxAmt.toFixed(2);
        totalInput.value    = total.toFixed(2);
        balanceInput.value  = balance.toFixed(2);
    }

    // Validate before submit
    document.getElementById('saleForm').addEventListener('submit', function(e) {
        var rows  = container.querySelectorAll('.item-row');
        var valid = true;
        var errors = [];

        rows.forEach(function(row) {
            var type  = row.querySelector('.inp-type').value;
            var id    = row.querySelector('.inp-item-id').value;
            var qty   = parseInt(row.querySelector('.inp-qty').value) || 0;
            var max   = parseInt(row.querySelector('.inp-qty').max);

            if (!type || !id) {
                valid = false;
                row.querySelector('.sel-type').style.borderColor = '#dc2626';
                row.querySelector('.sel-item').style.borderColor = '#dc2626';
                errors.push('Please select a type and item for every row.');
            } else if (!isNaN(max) && qty > max) {
                valid = false;
                row.querySelector('.inp-qty').style.borderColor = '#dc2626';
                errors.push('Quantity exceeds available stock (' + max + ' available). Please reduce the quantity.');
            }
        });

        if (!valid) {
            e.preventDefault();
            alert(errors[0]);
        }
    });

    // Add Item button
    document.getElementById('addItemBtn').addEventListener('click', function() {
        var whId = getWarehouseId();
        if (!whId) {
            alert('Please select a warehouse first before adding items.');
            return;
        }
        container.appendChild(createRow());
        recalcTotals();
    });

    // Pay Full button
    document.getElementById('payFullBtn').addEventListener('click', function() {
        paidInput.value = totalInput.value;
        recalcTotals();
    });

    discountInput.addEventListener('input', recalcTotals);
    taxInput.addEventListener('input',      recalcTotals);
    paidInput.addEventListener('input',     recalcTotals);

    // Warehouse change -> reload all items
    function bindWarehouseChange() {
        var whSel = document.getElementById('warehouseSelect');
        if (!whSel) return;

        function onWarehouseChange() {
            var whId = whSel.value;
            loadedWarehouse = null; // force reload
            if (whId) {
                loadWarehouseItems(whId, function() {
                    // Reset all existing rows item selection
                    container.querySelectorAll('.item-row').forEach(function(row) {
                        var type = row.querySelector('.inp-type').value;
                        if (type) {
                            row.querySelector('.sel-item').innerHTML = buildOptionsHtml(type);
                            row.querySelector('.inp-item-id').value  = '';
                            row.querySelector('.inp-price').value    = '0.00';
                        }
                    });
                    recalcTotals();
                });
            }
        }

        // Plain select change
        whSel.addEventListener('change', onWarehouseChange);

        // Also hook TomSelect onChange (fires after global init)
        document.addEventListener('DOMContentLoaded', function() {
            if (whSel._tomSelect) {
                whSel._tomSelect.on('change', onWarehouseChange);
            }
        });
    }

    // Init
    bindWarehouseChange();
    container.appendChild(createRow());
    recalcTotals();

    // Load items for default warehouse on page load
    var defaultWh = getWarehouseId();
    if (defaultWh) {
        loadWarehouseItems(defaultWh, null);
    }

})();
</script>
@endpush
