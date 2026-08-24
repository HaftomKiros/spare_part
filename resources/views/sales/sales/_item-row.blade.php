{{-- Sale item row template (also cloned by JS) --}}
<tr class="item-row">
    {{-- Hidden fields --}}
    <input type="hidden" name="items[{{ $index }}][item_type]" class="item-type-hidden" value="spare_part">
    <input type="hidden" name="items[{{ $index }}][item_id]"   class="item-id-hidden"   value="">
    <input type="hidden" name="items[{{ $index }}][total]"     class="row-total-input"  value="0">

    <td>
        <select name="items[{{ $index }}][item_type_display]" class="form-select form-select-sm item-type-select" style="min-width:110px">
            <option value="">Select…</option>
            <option value="vehicle">Vehicle</option>
            <option value="spare_part" selected>Spare Part</option>
        </select>
    </td>
    <td>
        <select class="form-select form-select-sm item-select" style="min-width:200px">
            <option value="">— Select item —</option>
        </select>
    </td>
    <td>
        <input type="number" name="items[{{ $index }}][unit_price]" class="form-control form-control-sm price-input"
               value="0.00" min="0" step="0.01" style="width:110px">
    </td>
    <td>
        <input type="number" name="items[{{ $index }}][quantity]" class="form-control form-control-sm qty-input"
               value="1" min="1" style="width:70px">
    </td>
    <td>
        <input type="number" name="items[{{ $index }}][discount]" class="form-control form-control-sm discount-input"
               value="0.00" min="0" step="0.01" style="width:90px">
    </td>
    <td>
        <span class="fw-semibold row-total">0.00</span>
    </td>
    <td>
        <button type="button" class="btn btn-sm btn-outline-danger remove-btn" style="display:none">
            <i class="fa fa-times"></i>
        </button>
    </td>
</tr>
