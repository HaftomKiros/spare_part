@extends('layouts.app')
@section('title', 'My Profile')
@section('breadcrumb')
    <li class="breadcrumb-item active">My Profile</li>
@endsection

@section('content')
@include('partials.page-header', ['title' => 'My Profile', 'subtitle' => 'Update your personal information and password'])

<form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
@csrf
@method('PUT')

<div class="row g-3">

    {{-- ── LEFT: Personal Info + Password ───────────────────── --}}
    <div class="col-12 col-lg-8">

        {{-- Personal Information --}}
        <div class="card mb-3">
            <div class="card-header">
                <i class="fa fa-user me-2 text-primary"></i>Personal Information
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text"
                               name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}"
                               required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email"
                               name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}"
                               required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text"
                               name="phone"
                               class="form-control @error('phone') is-invalid @enderror"
                               value="{{ old('phone', $user->phone) }}">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Read-only: Role & Status --}}
                    <div class="col-md-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="{{ $user->role?->display_name ?? '—' }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <input type="text" class="form-control"
                               value="{{ ucfirst($user->status) }}" disabled>
                    </div>
                </div>
            </div>
        </div>

        {{-- Change Password --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="fa fa-lock me-2 text-primary"></i>Change Password</span>
                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="collapse"
                        data-bs-target="#passwordSection"
                        aria-expanded="{{ $errors->hasAny(['current_password','password']) ? 'true' : 'false' }}">
                    <i class="fa fa-pen me-1"></i>Update
                </button>
            </div>
            <div class="collapse @if($errors->hasAny(['current_password','password'])) show @endif"
                 id="passwordSection">
                <div class="card-body border-top">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Current Password <span class="text-danger">*</span></label>
                            <input type="password"
                                   name="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   placeholder="Enter your current password">
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password"
                                   name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Min. 8 characters">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password"
                                   name="password_confirmation"
                                   class="form-control"
                                   placeholder="Repeat new password">
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── RIGHT: Avatar + Save ──────────────────────────────── --}}
    <div class="col-12 col-lg-4">

        {{-- Profile Photo --}}
        <div class="card mb-3">
            <div class="card-header">
                <i class="fa fa-image me-2 text-primary"></i>Profile Photo
            </div>
            <div class="card-body text-center">
                <img src="{{ $user->avatar_url }}"
                     class="rounded-circle mb-3"
                     width="90" height="90"
                     style="object-fit:cover;border:3px solid var(--border-color)"
                     alt="Avatar"
                     id="avatarPreview">
                <div class="mb-1">
                    <input type="file"
                           name="avatar"
                           id="avatarInput"
                           class="form-control form-control-sm @error('avatar') is-invalid @enderror"
                           accept="image/*">
                    @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-text">PNG, JPG — max 2 MB</div>
            </div>
        </div>

        {{-- Account Info (read-only) --}}
        <div class="card mb-3">
            <div class="card-header">
                <i class="fa fa-circle-info me-2 text-muted"></i>Account Info
            </div>
            <div class="card-body">
                <dl class="row mb-0" style="font-size:.88rem">
                    <dt class="col-6 text-muted fw-normal">Member Since</dt>
                    <dd class="col-6">{{ $user->created_at->format('d M Y') }}</dd>
                </dl>
            </div>
        </div>

        {{-- Save button --}}
        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-save me-1"></i>Save Changes
                </button>
            </div>
        </div>

    </div>
</div>
</form>

@push('scripts')
<script>
// Live avatar preview before upload
document.getElementById('avatarInput').addEventListener('change', function () {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('avatarPreview').src = e.target.result;
        reader.readAsDataURL(file);
    }
});
</script>
@endpush
@endsection
