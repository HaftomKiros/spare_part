@extends('layouts.app')
@section('title','Users')
@section('breadcrumb')
    <li class="breadcrumb-item active">Settings</li>
    <li class="breadcrumb-item active">Users</li>
@endsection
@section('content')
@include('partials.page-header',[
    'title'  =>'System Users',
    'subtitle'=>'Manage staff accounts and access',
    'actions'=>[['label'=>'Add User','route'=>'settings.users.create','icon'=>'fa-plus']],
])

<div class="card mb-3">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-12 col-md-4">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fa fa-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Name or email…" value="{{ request('search') }}">
        </div>
    </div>
    <div class="col-auto">
        <select name="role" class="form-select form-select-sm">
            <option value="">All Roles</option>
            @foreach($roles as $r)
                <option value="{{ $r->id }}" {{ request('role') == $r->id ? 'selected' : '' }}>{{ $r->display_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm">
            <option value="">All Status</option>
            <option value="active"   {{ request('status')==='active'?'selected':'' }}>Active</option>
            <option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Inactive</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-filter me-1"></i>Filter</button>
        @if(request()->hasAny(['search','role','status']))
            <a href="{{ route('settings.users.index') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="fa fa-xmark"></i></a>
        @endif
    </div>
</form>
</div>
</div>

<div class="card">
<div class="table-responsive">
<table class="table">
    <thead><tr><th>User</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Joined</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
        @forelse($users as $u)
        <tr>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <img src="{{ $u->avatar_url }}" class="rounded-circle" width="32" height="32" alt="">
                    <div>
                        <div class="fw-semibold small">{{ $u->name }}</div>
                        @if($u->id === auth()->id())<span class="badge" style="background:#e0e7ff;color:#3730a3;font-size:.72rem;padding:3px 8px;border-radius:5px" style="font-size:.65rem">You</span>@endif
                    </div>
                </div>
            </td>
            <td class="text-muted small">{{ $u->email }}</td>
            <td class="text-muted small">{{ $u->phone ?? '—' }}</td>
            <td>
                @if($u->role)
                    <span class="badge" style="background:#f1f5f9;color:#475569;font-size:.72rem;padding:3px 8px;border-radius:5px">{{ $u->role->display_name }}</span>
                @else
                    <span class="text-muted small">—</span>
                @endif
            </td>
            <td><span class="badge badge-status-{{ $u->status }}">{{ ucfirst($u->status) }}</span></td>
            <td class="text-muted small">{{ $u->created_at->format('M d, Y') }}</td>
            <td class="text-end">
                <a href="{{ route('settings.users.edit',$u) }}" class="btn btn-action btn-outline-primary me-1"><i class="fa fa-pen"></i></a>
                @if($u->id !== auth()->id())
                <button class="btn btn-action btn-outline-danger"
                    data-delete-url="{{ route('settings.users.destroy',$u) }}"
                    data-delete-message="Delete user '{{ $u->name }}'? This cannot be undone."><i class="fa fa-trash"></i></button>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted py-5">
            <i class="fa fa-users fs-2 d-block mb-2 opacity-25"></i>No users found.
        </td></tr>
        @endforelse
    </tbody>
</table>
</div>
@if($users->hasPages())<div class="card-body border-top py-3">{{ $users->links() }}</div>@endif
</div>
@include('partials.delete-modal')
@endsection
