@extends('layouts.app')
@section('title','Roles')
@section('breadcrumb')
    <li class="breadcrumb-item active">Settings</li>
    <li class="breadcrumb-item active">Roles</li>
@endsection
@section('content')
@include('partials.page-header',[
    'title'  =>'Roles & Permissions',
    'subtitle'=>'Control what each role can access',
    'actions'=>[['label'=>'Add Role','route'=>'settings.roles.create','icon'=>'fa-plus']],
])

<div class="card">
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Role</th><th>Description</th><th>Users</th><th>Permissions</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
        @forelse($roles as $role)
        <tr>
            <td>
                <div class="fw-semibold">{{ $role->display_name }}</div>
                <div class="text-muted small font-monospace">{{ $role->name }}</div>
            </td>
            <td class="text-muted small">{{ $role->description ?? '—' }}</td>
            <td><span class="badge" style="background:#f1f5f9;color:#475569;font-size:.72rem;padding:3px 8px;border-radius:5px">{{ $role->users_count }}</span></td>
            <td>
                @if(in_array('all', $role->permissions ?? []))
                    <span class="badge bg-primary">Full Access</span>
                @else
                    @foreach(array_slice($role->permissions ?? [], 0, 3) as $perm)
                        <span class="badge" style="background:#f1f5f9;color:#475569;font-size:.72rem;padding:3px 8px;border-radius:5px me-1" style="font-size:.7rem">{{ $perm }}</span>
                    @endforeach
                    @if(count($role->permissions ?? []) > 3)
                        <span class="text-muted small">+{{ count($role->permissions) - 3 }} more</span>
                    @endif
                @endif
            </td>
            <td class="text-end">
                <a href="{{ route('settings.roles.edit',$role) }}" class="btn btn-action btn-outline-primary me-1"><i class="fa fa-pen"></i></a>
                <button class="btn btn-action btn-outline-danger"
                    data-delete-url="{{ route('settings.roles.destroy',$role) }}"
                    data-delete-message="Delete role '{{ $role->display_name }}'?"><i class="fa fa-trash"></i></button>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center text-muted py-5">No roles defined.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</div>
@include('partials.delete-modal')
@endsection
