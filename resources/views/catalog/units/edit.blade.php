@extends('layouts.app')
@section('title', 'Edit Unit')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.units.index') }}">Units</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection
@section('content')
@include('partials.page-header', ['title' => 'Edit Unit', 'subtitle' => $unit->name])
<div class="row justify-content-center">
<div class="col-12 col-lg-6">
<div class="card">
<div class="card-header"><i class="fa fa-pen me-2 text-primary"></i>Edit Details</div>
<div class="card-body">
<form method="POST" action="{{ route('catalog.units.update', $unit) }}">
@csrf @method('PUT')
    <div class="mb-3">
        <label class="form-label">Unit Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $unit->name) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Abbreviation <span class="text-danger">*</span></label>
        <input type="text" name="abbreviation" class="form-control" value="{{ old('abbreviation', $unit->abbreviation) }}" required>
    </div>
    <div class="mb-4">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description', $unit->description) }}</textarea>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4"><i class="fa fa-save me-1"></i>Update</button>
        <a href="{{ route('catalog.units.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
    </div>
</form>
</div>
</div>
</div>
</div>
@endsection
