@extends('layouts.app')
@section('title', 'New Stock Adjustment')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('inventory.adjustments.index') }}">Adjustments</a></li>
    <li class="breadcrumb-item active">New</li>
@endsection
@section('content')
@include('partials.page-header', ['title' => 'New Stock Adjustment', 'subtitle' => $number])

<form method="POST" action="{{ route('inventory.adjustments.store') }}">
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
        <select name="adjustment_type" class="form-select @error('adjustment_type') is-invalid @enderror" required>
            <option value="">Select type…</option>
            <option value="increase" {{ old('adjustment_type') === 'increase' ? 'selected' : '' }}>Increase (+)</option>
            <option value="decrease" {{ old('adjustment_type') === 'decrease' ? 'selected' : '' }}>Decrease (-)</option>
        </select>
        @error('adjustment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label">Reason <span class="text-danger">*</span></label>
        <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="2"
                  placeholder="e.g. Physical count correction, Damaged items write-off…">{{ old('reason') }}</textarea>
        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
</div>
</div>

<!-- Items Table -->
<div class="card">
<div class="card-header d-flex align-items-center justify-content-between">
    <span><i class="fa fa-list me-2 text-primary"></i>Items to Adjust</span>
    <button type="button" class="btn btn-sm btn-outline-primary" id="addAdjRow">
        <i class="fa fa-plus me-1"></i>Add Item
    </button>
</div>
<div class="table-responsive">
<table class="table mb-0" id="adjItemsTable">
    <thead>
        <tr>
            <th style="width:40%">Item</th>
            <th>Type</th>
            <th>Current Stock</th>
            <th>Qty to Adjust</th>
            <th>Notes</th>
            <th></th>
        </tr>
    </thead>
    <tbody id="adjItemsBody">
        <tr class="adj-row" data-index="0">
            <td>
                <select name="items[0][item_id]" class="form-select form-select-sm item-select" required>
                    <option value="">Select item…</option>
                    <optgroup label="── Vehicles ──">
                        @foreach($vehicleTypes as $vt)
                            @foreach($vt->activeVehicleModels as $vm)
                                <option value="{{ $vm->id }}" data-type="vehicle" data-stock="{{ $vm->stock?->current_stock ?? 0 }}">
                                    [V] {{ $vm->brand }} {{ $vm->model_name }} (Stock: {{ $vm->stock?->current_stock ?? 0 }})
                                </option>
                            @endforeach
                        @endforeach
                    </optgroup>
                    @foreach($categories as $cat)
                        <optgroup label="{{ $cat->name }}">
                            @foreach($cat->spareParts as $part)
                                <option value="{{ $part->id }}" data-type="spare_part" data-stock="{{ $part->current_stock }}">
                                    [P] {{ $part->name }} ({{ $part->part_number }}) — Stock: {{ $part->current_stock }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <input type="hidden" name="items[0][item_type]" class="item-type-hidden" value="">
            </td>
            <td>
                <span class="badge bg-secondary item-type-badge">—</span>
            </td>
            <td>
                <span class="current-stock-display fw-semibold">—</span>
            </td>
            <td>
                <input type="number" name="items[0][quantity]" class="form-control form-control-sm" min="1" value="1" required style="width:80px">
            </td>
            <td>
                <input type="text" name="items[0][notes]" class="form-control form-control-sm" placeholder="Optional…">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger remove-adj-row" style="display:none">
                    <i class="fa fa-times"></i>
                </button>
            </td>
        </tr>
    </tbody>
</table>
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
        <span class="text-muted">Created by:</span>
        <strong class="float-end">{{ auth()->user()->name }}</strong>
    </div>
    <hr>
    <div class="d-grid">
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-save me-1"></i>Save Adjustment
        </button>
    </div>
    <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
</div>
</div>
</div>

</div>
</form>

@push('scripts')
<script>
let rowIndex = 1;

document.getElementById('addAdjRow').addEventListener('click', function () {
    const tbody    = document.getElementById('adjItemsBody');
    const template = tbody.querySelector('.adj-row').cloneNode(true);

    template.dataset.index = rowIndex;
    template.querySelectorAll('[name]').forEach(el => {
        el.name = el.name.replace(/\[\d+\]/, '[' + rowIndex + ']');
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
        if (el.tagName === 'INPUT' && el.type !== 'hidden') el.value = el.type === 'number' ? 1 : '';
    });
    template.querySelector('.item-type-hidden').value = '';
    template.querySelector('.item-type-badge').textContent = '—';
    template.querySelector('.current-stock-display').textContent = '—';
    template.querySelector('.remove-adj-row').style.display = '';

    tbody.appendChild(template);
    bindRowEvents(template);
    rowIndex++;
});

document.getElementById('adjItemsBody').addEventListener('click', function (e) {
    if (e.target.closest('.remove-adj-row')) {
        const rows = document.querySelectorAll('.adj-row');
        if (rows.length > 1) e.target.closest('.adj-row').remove();
    }
});

function bindRowEvents(row) {
    const sel = row.querySelector('.item-select');
    if (sel) {
        sel.addEventListener('change', function () {
            const opt  = this.options[this.selectedIndex];
            const type = opt?.dataset.type || '';
            const stock = opt?.dataset.stock ?? '—';
            row.querySelector('.item-type-hidden').value = type;
            row.querySelector('.item-type-badge').textContent = type === 'vehicle' ? 'Vehicle' : (type === 'spare_part' ? 'Part' : '—');
            row.querySelector('.item-type-badge').className = 'badge ' + (type === 'vehicle' ? 'bg-primary' : (type === 'spare_part' ? 'bg-success' : 'bg-secondary')) + ' item-type-badge';
            row.querySelector('.current-stock-display').textContent = stock;
        });
    }
}

// Bind on existing rows
document.querySelectorAll('.adj-row').forEach(bindRowEvents);
</script>
@endpush
@endsection
