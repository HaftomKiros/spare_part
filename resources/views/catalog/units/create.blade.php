@extends('layouts.app')
@section('title', 'Add Unit')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.units.index') }}">Units</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection
@section('content')
@include('partials.page-header', ['title' => 'Add Unit of Measure'])
<div class="row justify-content-center">
<div class="col-12 col-lg-6">
<div class="card">
<div class="card-header"><i class="fa fa-ruler me-2 text-primary"></i>Unit Details</div>
<div class="card-body">
<form method="POST" action="{{ route('catalog.units.store') }}">
@csrf
    <div class="mb-3">
        <label class="form-label">Unit Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name') }}" placeholder="e.g. Piece, Set, Box, Litre" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label">Abbreviation <span class="text-danger">*</span></label>
        <input type="text" name="abbreviation" class="form-control @error('abbreviation') is-invalid @enderror"
               value="{{ old('abbreviation') }}" placeholder="e.g. Pcs, Set, Box, L" maxlength="20" required>
        @error('abbreviation')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-4">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2" placeholder="Optional description…">{{ old('description') }}</textarea>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4"><i class="fa fa-save me-1"></i>Save</button>
        <a href="{{ route('catalog.units.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
    </div>
</form>
</div>
</div>
</div>
</div>
@endsection
