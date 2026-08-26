@extends('layouts.app')
@section('title','Add User')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('settings.users.index') }}">Users</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'Add User'])
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
<div class="card">
<div class="card-header"><i class="fa fa-user-plus me-2 text-primary"></i>User Details</div>
<div class="card-body">
<form method="POST" action="{{ route('settings.users.store') }}">
@csrf

{{-- ── Access Level ──────────────────────────────────────── --}}
<div class="mb-4">
    <label class="form-label fw-semibold">Access Level <span class="text-danger">*</span></label>
    <div class="row g-2" id="accessLevelCards">

        {{-- Regular User --}}
        <div class="col-12 col-md-4">
            <label class="access-level-card d-block p-3 rounded-3 cursor-pointer"
                   style="border:2px solid #e5e7eb;cursor:pointer;transition:all .2s"
                   for="al_regular" id="card_regular">
                <div class="d-flex align-items-center gap-3">
                    <input type="radio" name="access_level" id="al_regular" value="regular"
                           class="form-check-input mt-0 flex-shrink-0"
                           {{ old('access_level','regular') === 'regular' ? 'checked' : '' }}
                           onchange="updateAccessCard()">
                    <div>
                        <div class="fw-semibold" style="font-size:.9rem">
                            <i class="fa fa-user me-1 text-muted"></i>Regular User
                        </div>
                        <div class="text-muted" style="font-size:.78rem">
                            Sees only their own sales &amp; purchases in assigned warehouses
                        </div>
                    </div>
                </div>
            </label>
        </div>

        {{-- Warehouse Manager --}}
        <div class="col-12 col-md-4">
            <label class="access-level-card d-block p-3 rounded-3 cursor-pointer"
                   style="border:2px solid #e5e7eb;cursor:pointer;transition:all .2s"
                   for="al_manager" id="card_manager">
                <div class="d-flex align-items-center gap-3">
                    <input type="radio" name="access_level" id="al_manager" value="manager"
                           class="form-check-input mt-0 flex-shrink-0"
                           {{ old('access_level') === 'manager' ? 'checked' : '' }}
                           onchange="updateAccessCard()">
                    <div>
                        <div class="fw-semibold" style="font-size:.9rem">
                            <i class="fa fa-user-tie me-1 text-warning"></i>Warehouse Manager
                        </div>
                        <div class="text-muted" style="font-size:.78rem">
                            Sees ALL transactions in assigned warehouses (no user filter)
                        </div>
                    </div>
                </div>
            </label>
        </div>

        {{-- Super Admin --}}
        <div class="col-12 col-md-4">
            <label class="access-level-card d-block p-3 rounded-3 cursor-pointer"
                   style="border:2px solid #e5e7eb;cursor:pointer;transition:all .2s"
                   for="al_super_admin" id="card_super_admin">
                <div class="d-flex align-items-center gap-3">
                    <input type="radio" name="access_level" id="al_super_admin" value="super_admin"
                           class="form-check-input mt-0 flex-shrink-0"
                           {{ old('access_level') === 'super_admin' ? 'checked' : '' }}
                           onchange="updateAccessCard()">
                    <div>
                        <div class="fw-semibold" style="font-size:.9rem">
                            <i class="fa fa-shield-halved me-1 text-danger"></i>Super Admin
                        </div>
                        <div class="text-muted" style="font-size:.78rem">
                            Full access — all warehouses, all users, no restrictions
                        </div>
                    </div>
                </div>
            </label>
        </div>

    </div>
</div>

<hr class="mb-4">

{{-- ── Personal Details ─────────────────────────────────── --}}
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Full Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Role</label>
        <select name="role_id" class="form-select ts-select">
            <option value="">No Role</option>
            @foreach($roles as $r)
                <option value="{{ $r->id }}" {{ old('role_id') == $r->id ? 'selected' : '' }}>{{ $r->display_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Password <span class="text-danger">*</span></label>
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
        <input type="password" name="password_confirmation" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Status</label>
        <select name="status" class="form-select ts-select">
            <option value="active">Active</option>
            <option value="inactive" {{ old('status')==='inactive'?'selected':'' }}>Inactive</option>
        </select>
    </div>

    {{-- Warehouses — hidden for super_admin (they access all) --}}
    <div class="col-12" id="warehouseField">
        <label class="form-label">
            <i class="fa fa-warehouse me-1 text-muted"></i>Assigned Warehouses
            <span class="text-muted small ms-1">(leave empty = access all)</span>
        </label>
        <select name="warehouse_ids[]" class="form-select ts-select" multiple placeholder="Select warehouses...">
            @foreach($warehouses as $w)
                <option value="{{ $w->id }}"
                    {{ in_array($w->id, old('warehouse_ids', [])) ? 'selected' : '' }}>
                    {{ $w->name }}{{ $w->city ? ' — '.$w->city : '' }}
                </option>
            @endforeach
        </select>
        @error('warehouse_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary px-4"><i class="fa fa-save me-1"></i>Save User</button>
    <a href="{{ route('settings.users.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>
</form>
</div>
</div>
</div>
</div>
@endsection

@push('scripts')
<script>
function updateAccessCard() {
    var levels = ['regular', 'manager', 'super_admin'];
    var colors = { regular: '#5b4fcf', manager: '#d97706', super_admin: '#dc2626' };
    var selected = document.querySelector('input[name="access_level"]:checked')?.value || 'regular';

    levels.forEach(function(level) {
        var card = document.getElementById('card_' + level);
        if (!card) return;
        if (level === selected) {
            card.style.borderColor  = colors[level];
            card.style.background   = colors[level] + '10';
            card.style.boxShadow    = '0 0 0 3px ' + colors[level] + '22';
        } else {
            card.style.borderColor  = '#e5e7eb';
            card.style.background   = '#fff';
            card.style.boxShadow    = '';
        }
    });

    // Super admin doesn't need warehouse assignment
    var wf = document.getElementById('warehouseField');
    if (wf) wf.style.opacity = selected === 'super_admin' ? '.4' : '1';
}

document.addEventListener('DOMContentLoaded', updateAccessCard);
</script>
@endpush
