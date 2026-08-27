@extends('layouts.app')
@section('title', $warehouse->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('settings.warehouses.index') }}">Warehouses</a></li>
    <li class="breadcrumb-item active">{{ $warehouse->name }}</li>
@endsection
@section('content')
@include('partials.page-header',[
    'title'   => $warehouse->name,
    'subtitle'=> $warehouse->code . ' - ' . ($warehouse->city ?? ''),
    'actions' => [
        ['label'=>'Edit','url'=>route('settings.warehouses.edit', $warehouse),'icon'=>'fa-pen','class'=>'btn-outline-primary'],
        ['label'=>'Transfer Stock','url'=>'#transferModal','icon'=>'fa-right-left','class'=>'btn-outline-warning'],
    ],
])

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card brand">
            <div class="stat-icon brand"><i class="fa fa-gears"></i></div>
            <div class="stat-value">{{ $parts->count() }}</div>
            <div class="stat-label">Spare Part SKUs</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card success">
            <div class="stat-icon success"><i class="fa fa-motorcycle"></i></div>
            <div class="stat-value">{{ $vehicles->count() }}</div>
            <div class="stat-label">Vehicle Models</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card warning">
            <div class="stat-icon warning"><i class="fa fa-triangle-exclamation"></i></div>
            <div class="stat-value">{{ $parts->where('current_stock', '<=', 0)->count() + $vehicles->where('current_stock', '<=', 0)->count() }}</div>
            <div class="stat-label">Out of Stock</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card info">
            <div class="stat-icon info"><i class="fa fa-warehouse"></i></div>
            <div class="stat-value">Br {{ number_format($warehouse->total_stock_value, 0) }}</div>
            <div class="stat-label">Stock Value</div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3" id="whTabs">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#parts-tab"><i class="fa fa-gears me-1"></i>Spare Parts ({{ $parts->count() }})</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#vehicles-tab"><i class="fa fa-motorcycle me-1"></i>Vehicles ({{ $vehicles->count() }})</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#movements-tab"><i class="fa fa-history me-1"></i>Recent Movements</a></li>
</ul>

<div class="tab-content">

{{-- Spare Parts --}}
<div class="tab-pane fade show active" id="parts-tab">
<div class="card">
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Part</th><th>Category</th><th>Unit</th><th>In Stock</th><th>Reorder</th><th>Status</th><th>Value</th></tr></thead>
    <tbody>
        @forelse($parts as $p)
        <tr>
            <td>
                <div class="fw-semibold small">{{ $p->name }}</div>
                <div class="text-muted" style="font-size:.72rem">{{ $p->part_number }}</div>
            </td>
            <td class="text-muted small">{{ $p->category }}</td>
            <td class="text-muted small">{{ $p->unit }}</td>
            <td>
                <span class="fw-bold {{ $p->current_stock <= 0 ? 'text-danger' : ($p->current_stock <= $p->reorder_level ? 'text-warning' : 'text-success') }}">
                    {{ $p->current_stock }}
                </span>
            </td>
            <td class="text-muted">{{ $p->reorder_level }}</td>
            <td>
                @if($p->current_stock <= 0)
                    <span class="stock-pill out">Out of Stock</span>
                @elseif($p->current_stock <= $p->reorder_level)
                    <span class="stock-pill low">Low Stock</span>
                @else
                    <span class="stock-pill in-stock">In Stock</span>
                @endif
            </td>
            <td class="text-muted small">
                {{ $p->last_purchase_price > 0 ? 'Br '.number_format($p->current_stock * $p->last_purchase_price, 2) : '—' }}
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted py-4">No spare parts in this warehouse yet.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</div>
</div>

{{-- Vehicles --}}
<div class="tab-pane fade" id="vehicles-tab">
<div class="card">
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Model</th><th>Type</th><th>In Stock</th><th>Reorder</th><th>Status</th><th>Value</th></tr></thead>
    <tbody>
        @forelse($vehicles as $v)
        <tr>
            <td class="fw-semibold small">{{ $v->brand }} {{ $v->model_name }} {{ $v->model_code ? '('.$v->model_code.')' : '' }}</td>
            <td><span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:.7rem">{{ $v->type_name }}</span></td>
            <td>
                <span class="fw-bold {{ $v->current_stock <= 0 ? 'text-danger' : ($v->current_stock <= $v->reorder_level ? 'text-warning' : 'text-success') }}">
                    {{ $v->current_stock }}
                </span>
            </td>
            <td class="text-muted">{{ $v->reorder_level }}</td>
            <td>
                @if($v->current_stock <= 0)
                    <span class="stock-pill out">Out of Stock</span>
                @elseif($v->current_stock <= $v->reorder_level)
                    <span class="stock-pill low">Low Stock</span>
                @else
                    <span class="stock-pill in-stock">In Stock</span>
                @endif
            </td>
            <td class="text-muted small">Br {{ number_format($v->current_stock * $v->buying_price, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted py-4">No vehicles in this warehouse yet.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</div>
</div>

{{-- Movements --}}
<div class="tab-pane fade" id="movements-tab">
<div class="card">
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Date</th><th>Item</th><th>Type</th><th>Direction</th><th>Qty</th><th>Before</th><th>After</th><th>By</th></tr></thead>
    <tbody>
        @forelse($movements as $mv)
        <tr>
            <td class="text-muted small">{{ \Carbon\Carbon::parse($mv->created_at)->format('M d, Y H:i') }}</td>
            <td class="small fw-semibold">
                {{ $mv->item_type === 'spare_part' ? ($mv->part_name ?? '-') : ($mv->brand.' '.$mv->model_name) }}
            </td>
            <td><span class="badge bg-secondary" style="font-size:.7rem">{{ ucfirst(str_replace('_',' ',$mv->movement_type)) }}</span></td>
            <td>
                @php $in = in_array($mv->movement_type,['purchase','return_in','adjustment_in','opening']); @endphp
                <span class="{{ $in ? 'text-success' : 'text-danger' }}">
                    <i class="fa fa-{{ $in ? 'arrow-down' : 'arrow-up' }} me-1"></i>{{ $in ? 'IN' : 'OUT' }}
                </span>
            </td>
            <td class="fw-bold {{ $in ? 'text-success' : 'text-danger' }}">{{ $in ? '+' : '-' }}{{ $mv->quantity }}</td>
            <td class="text-muted">{{ $mv->quantity_before }}</td>
            <td class="fw-semibold">{{ $mv->quantity_after }}</td>
            <td class="small text-muted">{{ $mv->user_name }}</td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center text-muted py-4">No movements yet.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</div>
</div>

</div>{{-- /tab-content --}}

{{-- Transfer Modal --}}
<div class="modal fade" id="transferModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content border-0 shadow">
<div class="modal-header border-0 pb-0">
    <h5 class="modal-title fw-bold"><i class="fa fa-right-left me-2" style="color:var(--brand-1)"></i>Transfer Stock</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<form method="POST" action="{{ route('settings.warehouses.transfer') }}">
@csrf
<input type="hidden" name="from_warehouse_id" value="{{ $warehouse->id }}">
<div class="mb-3">
    <label class="form-label">To Warehouse <span class="text-danger">*</span></label>
    <select name="to_warehouse_id" class="form-select" required>
        <option value="">Select destination...</option>
        @foreach(\App\Models\Warehouse::active()->where('id','!=',$warehouse->id)->get() as $wh)
            <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->city }})</option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label class="form-label">Item Type <span class="text-danger">*</span></label>
    <select name="item_type" class="form-select" id="transferType" required>
        <option value="">Select type...</option>
        <option value="spare_part">Spare Part</option>
        <option value="vehicle">Vehicle</option>
    </select>
</div>
<div class="mb-3">
    <label class="form-label">Item <span class="text-danger">*</span></label>
    <select name="item_id" id="transferItem" class="form-select" required disabled>
        <option value="">- Select type first -</option>
    </select>
</div>
<div class="mb-3">
    <label class="form-label">Quantity <span class="text-danger">*</span></label>
    <input type="number" name="quantity" class="form-control" min="1" value="1" required>
</div>
<div class="mb-3">
    <label class="form-label">Notes</label>
    <input type="text" name="notes" class="form-control" placeholder="Reason for transfer...">
</div>
<div class="d-flex gap-2">
    <button type="submit" class="btn btn-warning px-4"><i class="fa fa-right-left me-1"></i>Transfer</button>
    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
</div>
</form>
</div>
</div>
</div>
</div>

@endsection
@push('scripts')
<script>
// Auto-open transfer modal from button
document.querySelector('a[href="#transferModal"]')?.addEventListener('click', function(e) {
    e.preventDefault();
    new bootstrap.Modal(document.getElementById('transferModal')).show();
});

// Populate items based on type
const PARTS    = @json($parts->map(fn($p) => ['id'=>$p->id,'name'=>$p->name.' ('.$p->part_number.') - Stock:'.$p->current_stock]));
const VEHICLES = @json($vehicles->map(fn($v) => ['id'=>$v->id,'name'=>$v->brand.' '.$v->model_name.' - Stock:'.$v->current_stock]));

document.getElementById('transferType')?.addEventListener('change', function() {
    const sel  = document.getElementById('transferItem');
    const data = this.value === 'spare_part' ? PARTS : (this.value === 'vehicle' ? VEHICLES : []);
    sel.innerHTML = '<option value="">- Select item -</option>';
    data.forEach(d => {
        const o = document.createElement('option');
        o.value = d.id; o.textContent = d.name;
        sel.appendChild(o);
    });
    sel.disabled = data.length === 0;
});
</script>
@endpush
