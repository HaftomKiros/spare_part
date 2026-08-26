@extends('layouts.app')
@section('title','Expense Detail')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('expenses.index') }}">Expenses</a></li>
    <li class="breadcrumb-item active">{{ $expense->expense_number }}</li>
@endsection
@section('content')
@include('partials.page-header',[
    'title'   => $expense->expense_number,
    'subtitle'=> $expense->title,
    'actions' => array_filter([
        auth()->user()->hasPermission('expenses.edit')
            ? ['label'=>'Edit','url'=>route('expenses.edit',$expense),'icon'=>'fa-pen','class'=>'btn-outline-primary']
            : null,
    ]),
])

<div class="row justify-content-center">
<div class="col-12 col-lg-7">
<div class="card">
<div class="card-header">
    <i class="fa fa-money-bill-wave me-2 text-danger"></i>Expense Information
</div>
<div class="card-body">
<dl class="row mb-0">
    <dt class="col-sm-4 text-muted fw-normal">Reference #</dt>
    <dd class="col-sm-8">{{ $expense->expense_number }}</dd>

    <dt class="col-sm-4 text-muted fw-normal">Category</dt>
    <dd class="col-sm-8">
        <span class="badge bg-secondary-subtle text-secondary">
            {{ $expense->category?->name ?? '—' }}
        </span>
    </dd>

    <dt class="col-sm-4 text-muted fw-normal">Title</dt>
    <dd class="col-sm-8 fw-semibold">{{ $expense->title }}</dd>

    <dt class="col-sm-4 text-muted fw-normal">Amount</dt>
    <dd class="col-sm-8 fw-bold text-danger fs-5">Br {{ number_format($expense->amount, 2) }}</dd>

    <dt class="col-sm-4 text-muted fw-normal">Date</dt>
    <dd class="col-sm-8">{{ $expense->expense_date->format('d M Y') }}</dd>

    <dt class="col-sm-4 text-muted fw-normal">Payment Method</dt>
    <dd class="col-sm-8">{{ $expense->payment_method_label }}</dd>

    <dt class="col-sm-4 text-muted fw-normal">Warehouse</dt>
    <dd class="col-sm-8">{{ $expense->warehouse?->name ?? 'Company-wide' }}</dd>

    @if($expense->reference_number)
    <dt class="col-sm-4 text-muted fw-normal">Receipt / Voucher</dt>
    <dd class="col-sm-8">{{ $expense->reference_number }}</dd>
    @endif

    @if($expense->notes)
    <dt class="col-sm-4 text-muted fw-normal">Notes</dt>
    <dd class="col-sm-8">{{ $expense->notes }}</dd>
    @endif

    <dt class="col-sm-4 text-muted fw-normal">Recorded By</dt>
    <dd class="col-sm-8">{{ $expense->user?->name }}</dd>

    <dt class="col-sm-4 text-muted fw-normal">Recorded At</dt>
    <dd class="col-sm-8">{{ $expense->created_at->format('d M Y H:i') }}</dd>
</dl>
</div>
</div>

<div class="d-flex gap-2 mt-3">
    @if(auth()->user()->hasPermission('expenses.edit'))
    <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-primary px-4">
        <i class="fa fa-pen me-1"></i>Edit
    </a>
    @endif
    <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary px-4">Back</a>
    @if(auth()->user()->hasPermission('expenses.delete'))
    <button class="btn btn-outline-danger ms-auto"
            data-delete-url="{{ route('expenses.destroy', $expense) }}"
            data-delete-message="Delete expense '{{ $expense->title }}'? This cannot be undone.">
        <i class="fa fa-trash me-1"></i>Delete
    </button>
    @endif
</div>
</div>
</div>
@endsection
