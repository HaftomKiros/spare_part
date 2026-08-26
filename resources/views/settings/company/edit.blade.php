@extends('layouts.app')
@section('title','Company Profile')
@section('breadcrumb')
    <li class="breadcrumb-item active">Settings</li>
    <li class="breadcrumb-item active">Company Profile</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'Company Profile','subtitle'=>'Configure your business information'])

<form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data">
@csrf @method('PUT')
<div class="row g-3">

<div class="col-12 col-lg-8">
<div class="card mb-3">
<div class="card-header"><i class="fa fa-building me-2 text-primary"></i>Business Information</div>
<div class="card-body">
<div class="row g-3">
    <div class="col-12">
        <label class="form-label">Company Name <span class="text-danger">*</span></label>
        <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror"
               value="{{ old('company_name', $company->company_name) }}" required>
        @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="company_phone" class="form-control" value="{{ old('company_phone', $company->company_phone) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="company_email" class="form-control" value="{{ old('company_email', $company->company_email) }}">
    </div>
    <div class="col-12">
        <label class="form-label">Address</label>
        <textarea name="company_address" class="form-control" rows="2">{{ old('company_address', $company->company_address) }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label">Website</label>
        <input type="url" name="website" class="form-control" value="{{ old('website', $company->website) }}" placeholder="https://…">
    </div>
    <div class="col-md-6">
        <label class="form-label">TIN / Tax Number</label>
        <input type="text" name="tax_number" class="form-control" value="{{ old('tax_number', $company->tax_number) }}">
    </div>
</div>
</div>
</div>

<div class="card">
<div class="card-header"><i class="fa fa-coins me-2 text-primary"></i>Currency</div>
<div class="card-body">
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Currency Code <span class="text-danger">*</span></label>
        <input type="text" name="currency" class="form-control" value="{{ old('currency', $company->currency) }}" placeholder="ETB, USD, EUR…" maxlength="10" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Currency Symbol <span class="text-danger">*</span></label>
        <input type="text" name="currency_symbol" class="form-control" value="{{ old('currency_symbol', $company->currency_symbol) }}" placeholder="Br, $, €…" maxlength="5" required>
    </div>
</div>
</div>
</div>
</div>

<div class="col-12 col-lg-4">
<div class="card mb-3">
<div class="card-header"><i class="fa fa-image me-2 text-primary"></i>Company Logo</div>
<div class="card-body text-center">
    @if($company->company_logo)
        <img src="{{ asset('storage/'.$company->company_logo) }}" class="mb-3 rounded" style="max-height:80px;max-width:180px" alt="Logo">
    @else
        <div class="mb-3 p-4 bg-light rounded text-muted small">
            <i class="fa fa-image fa-2x mb-1 d-block opacity-25"></i>No logo uploaded
        </div>
    @endif
    <input type="file" name="company_logo" class="form-control form-control-sm" accept="image/*">
    <div class="form-text">PNG, JPG — max 2 MB</div>
</div>
</div>

<div class="card">
<div class="card-body">
    <button type="submit" class="btn btn-primary w-100">
        <i class="fa fa-save me-1"></i>Save Settings
    </button>
</div>
</div>
</div>

</div>
</form>
@endsection
