@extends('layouts.app')
@section('title','Expense Categories')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('expenses.index') }}">Expenses</a></li>
    <li class="breadcrumb-item active">Categories</li>
@endsection
@section('content')
@include('partials.page-header',[
    'title'   => 'Expense Categories',
    'subtitle'=> 'Manage categories like Salary, Rent, Utilities…',
])

<div class="row g-3">

{{-- Add Category Form --}}
<div class="col-12 col-md-4">
<div class="card">
<div class="card-header"><i class="fa fa-plus me-2 text-primary"></i>Add Category</div>
<div class="card-body">
<form method="POST" action="{{ route('expense-categories.store') }}">
@csrf
<div class="mb-3">
    <label class="form-label">Name <span class="text-danger">*</span></label>
    <input type="text" name="name"
           class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name') }}"
           placeholder="e.g. Salary, Rent, Utilities…" required>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label">Description</label>
    <input type="text" name="description" class="form-control"
           value="{{ old('description') }}" placeholder="Optional description">
</div>
<button type="submit" class="btn btn-primary w-100">
    <i class="fa fa-save me-1"></i>Save Category
</button>
</form>
</div>
</div>
</div>

{{-- Categories List --}}
<div class="col-12 col-md-8">
<div class="card">
<div class="table-responsive">
<table class="table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Description</th>
            <th>Expenses</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($categories as $cat)
        <tr>
            <td class="fw-semibold">{{ $cat->name }}</td>
            <td class="text-muted small">{{ $cat->description ?? '—' }}</td>
            <td>
                <span class="badge bg-secondary-subtle text-secondary">
                    {{ $cat->expenses_count }}
                </span>
            </td>
            <td>
                @if($cat->is_active)
                    <span class="badge bg-success-subtle text-success">Active</span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                @endif
            </td>
            <td class="text-end">
                {{-- Inline edit form --}}
                <button class="btn btn-action btn-outline-primary me-1"
                        type="button"
                        onclick="toggleEdit({{ $cat->id }})">
                    <i class="fa fa-pen"></i>
                </button>
                @if($cat->expenses_count == 0)
                <form method="POST" action="{{ route('expense-categories.destroy', $cat) }}"
                      class="d-inline"
                      onsubmit="return confirm('Delete category \'{{ $cat->name }}\'?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-action btn-outline-danger"><i class="fa fa-trash"></i></button>
                </form>
                @endif
            </td>
        </tr>
        {{-- Inline edit row --}}
        <tr id="edit-row-{{ $cat->id }}" style="display:none;background:#f8f9fa">
            <td colspan="5">
                <form method="POST" action="{{ route('expense-categories.update', $cat) }}"
                      class="row g-2 align-items-end p-1">
                    @csrf @method('PUT')
                    <div class="col-md-4">
                        <input type="text" name="name" class="form-control form-control-sm"
                               value="{{ $cat->name }}" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="description" class="form-control form-control-sm"
                               value="{{ $cat->description }}" placeholder="Description">
                    </div>
                    <div class="col-md-2">
                        <select name="is_active" class="form-select form-select-sm">
                            <option value="1" {{ $cat->is_active ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ ! $cat->is_active ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-1"
                                onclick="toggleEdit({{ $cat->id }})">Cancel</button>
                    </div>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="text-center text-muted py-4">
                No categories yet. Add your first one.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>
@if($categories->hasPages())
    <div class="card-body border-top py-3">{{ $categories->links() }}</div>
@endif
</div>
</div>

</div>
@endsection

@push('scripts')
<script>
function toggleEdit(id) {
    var row = document.getElementById('edit-row-' + id);
    row.style.display = row.style.display === 'none' ? '' : 'none';
}
</script>
@endpush
