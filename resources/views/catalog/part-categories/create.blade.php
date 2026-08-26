@extends('layouts.app')
@section('title', 'Add Part Category')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.part-categories.index') }}">Part Categories</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection
@section('content')
@include('partials.page-header', ['title' => 'Add Part Category'])
<div class="row justify-content-center">
<div class="col-12 col-lg-7">
<div class="card">
<div class="card-header"><i class="fa fa-layer-group me-2 text-primary"></i>Category Details</div>
<div class="card-body">
<form method="POST" action="{{ route('catalog.part-categories.store') }}">
@csrf
    <div class="mb-3">
        <label class="form-label">Category Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name') }}" placeholder="e.g. Engine Parts" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label">Parent Category</label>
        <select name="parent_id" class="form-select">
            <option value="">— None (Root Category) —</option>
            @foreach($parents as $p)
                <option value="{{ $p->id }}" {{ old('parent_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Icon Class <span class="text-muted small">(Font Awesome)</span></label>
        <div class="input-group">
            <span class="input-group-text" id="iconPreview"><i class="fa fa-layer-group"></i></span>
            <input type="text" name="icon" id="iconInput" class="form-control"
                   value="{{ old('icon', 'fa-cogs') }}" placeholder="fa-cogs">
        </div>
        <div class="form-text">Example: fa-cogs, fa-bolt, fa-car, fa-stop-circle</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
    </div>
    <div class="mb-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="active">Active</option>
            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4"><i class="fa fa-save me-1"></i>Save</button>
        <a href="{{ route('catalog.part-categories.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
    </div>
</form>
</div>
</div>
</div>
</div>
@endsection
@push('scripts')
<script>
document.getElementById('iconInput')?.addEventListener('input', function() {
    document.getElementById('iconPreview').innerHTML = '<i class="fa ' + this.value + '"></i>';
});
</script>
@endpush
