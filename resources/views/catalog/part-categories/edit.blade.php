@extends('layouts.app')
@section('title', 'Edit Part Category')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.part-categories.index') }}">Part Categories</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection
@section('content')
@include('partials.page-header', ['title' => 'Edit Category', 'subtitle' => $partCategory->name])
<div class="row justify-content-center">
<div class="col-12 col-lg-7">
<div class="card">
<div class="card-header"><i class="fa fa-pen me-2 text-primary"></i>Edit Details</div>
<div class="card-body">
<form method="POST" action="{{ route('catalog.part-categories.update', $partCategory) }}">
@csrf @method('PUT')
    <div class="mb-3">
        <label class="form-label">Category Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $partCategory->name) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Parent Category</label>
        <select name="parent_id" class="form-select">
            <option value="">— None (Root) —</option>
            @foreach($parents as $p)
                <option value="{{ $p->id }}" {{ old('parent_id', $partCategory->parent_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Icon Class</label>
        <div class="input-group">
            <span class="input-group-text" id="iconPreview"><i class="fa {{ $partCategory->icon }}"></i></span>
            <input type="text" name="icon" id="iconInput" class="form-control" value="{{ old('icon', $partCategory->icon) }}">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description', $partCategory->description) }}</textarea>
    </div>
    <div class="mb-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="active"   {{ old('status', $partCategory->status) === 'active'   ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $partCategory->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4"><i class="fa fa-save me-1"></i>Update</button>
        <a href="{{ route('catalog.part-categories.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
    </div>
</form>
</div>
</div>
</div>
</div>
@push('scripts')
<script>
document.getElementById('iconInput')?.addEventListener('input', function() {
    document.getElementById('iconPreview').innerHTML = '<i class="fa ' + this.value + '"></i>';
});
</script>
@endpush
@endsection
