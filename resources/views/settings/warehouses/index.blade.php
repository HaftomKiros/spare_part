@extends('layouts.app')
@section('title','Warehouses')
@section('breadcrumb')
    <li class="breadcrumb-item active">Settings</li>
    <li class="breadcrumb-item active">Warehouses</li>
@endsection
@section('content')
@include('partials.page-header',[
    'title'   => 'Warehouses / Stock Locations',
    'subtitle'=> 'Manage multiple stock locations (Mekelle, Addis Ababa, etc.)',
    'actions' => [['label'=>'Add Warehouse','route'=>'settings.warehouses.create','icon'=>'fa-plus']],
])

<div class="row g-3 mb-4">
@foreach($warehouses as $wh)
<div class="col-12 col-md-6 col-xl-4">
<div class="card h-100" style="border-top:3px solid var(--brand-1)">
<div class="card-body">
    <div class="d-flex align-items-start justify-content-between mb-3">
        <div class="d-flex align-items-center gap-3">
            <div style="width:46px;height:46px;background:var(--brand-light);border-radius:12px;display:flex;align-items:center;justify-content:center">
                <i class="fa fa-warehouse" style="color:var(--brand-1);font-size:1.2rem"></i>
            </div>
            <div>
                <div class="fw-bold">{{ $wh->name }}</div>
                <div class="text-muted small">{{ $wh->code }} • {{ $wh->city ?? '—' }}</div>
            </div>
        </div>
        <div class="d-flex gap-1">
            @if($wh->is_default)
                <span class="badge" style="background:#d1fae5;color:#065f46;font-size:.7rem">Default</span>
            @endif
            <span class="badge badge-status-{{ $wh->status }}">{{ ucfirst($wh->status) }}</span>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-4 text-center p-2 rounded-3" style="background:var(--brand-light)">
            <div class="fw-bold" style="color:var(--brand-1)">{{ $wh->parts_count }}</div>
            <div class="text-muted" style="font-size:.7rem">Parts</div>
        </div>
        <div class="col-4 text-center p-2 rounded-3" style="background:#d1fae5">
            <div class="fw-bold text-success">{{ $wh->vehicles_count }}</div>
            <div class="text-muted" style="font-size:.7rem">Vehicles</div>
        </div>
        <div class="col-4 text-center p-2 rounded-3" style="background:{{ $wh->low_stock > 0 ? '#fee2e2' : '#f0fdf4' }}">
            <div class="fw-bold {{ $wh->low_stock > 0 ? 'text-danger' : 'text-success' }}">{{ $wh->low_stock }}</div>
            <div class="text-muted" style="font-size:.7rem">Low Stock</div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted small">Stock Value</span>
        <span class="fw-semibold">Br {{ number_format($wh->stock_value, 2) }}</span>
    </div>

    @if($wh->manager)
    <div class="text-muted small mb-3">
        <i class="fa fa-user me-1"></i>{{ $wh->manager }}
        @if($wh->phone) · <i class="fa fa-phone me-1"></i>{{ $wh->phone }}@endif
    </div>
    @endif

    <div class="d-flex gap-2">
        <a href="{{ route('settings.warehouses.show', $wh) }}" class="btn btn-sm btn-outline-primary flex-grow-1">
            <i class="fa fa-eye me-1"></i>View Stock
        </a>
        <a href="{{ route('settings.warehouses.edit', $wh) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa fa-pen"></i>
        </a>
        @if(!$wh->is_default)
        <button class="btn btn-sm btn-outline-danger"
                data-delete-url="{{ route('settings.warehouses.destroy', $wh) }}"
                data-delete-message="Delete warehouse '{{ $wh->name }}'?">
            <i class="fa fa-trash"></i>
        </button>
        @endif
    </div>
</div>
</div>
</div>
@endforeach
</div>

@include('partials.delete-modal')
@endsection
