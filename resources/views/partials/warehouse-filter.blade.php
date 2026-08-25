{{--
    Reusable warehouse dropdown filter.
    Required variables: $warehouses (collection), $warehouseId (int|null)
    Optional: $extraParams (array of [name => value] to add as hidden inputs)
--}}
<div class="col-auto">
    <label class="form-label small mb-1"><i class="fa fa-warehouse me-1"></i>Warehouse</label>
    <select name="warehouse_id" class="form-select form-select-sm" style="min-width:160px">
        <option value="" {{ !$warehouseId ? 'selected' : '' }}>All Warehouses</option>
        @foreach($warehouses as $wh)
            <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>
                {{ $wh->name }}
            </option>
        @endforeach
    </select>
</div>
