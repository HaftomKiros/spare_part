@extends('layouts.app')
@section('title','Expenses Report')
@section('breadcrumb')
    <li class="breadcrumb-item active">Reports</li>
    <li class="breadcrumb-item active">Expenses</li>
@endsection
@section('content')
@include('partials.page-header',['title'=>'Expenses Report','subtitle'=>'Business expenses by period and category'])

{{-- Filters --}}
<div class="card mb-4">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-auto">
        <label class="form-label small mb-1">From</label>
        <input type="date" name="date_from" class="form-control form-control-sm"
               value="{{ $dateFrom }}">
    </div>
    <div class="col-auto">
        <label class="form-label small mb-1">To</label>
        <input type="date" name="date_to" class="form-control form-control-sm"
               value="{{ $dateTo }}">
    </div>
    <div class="col-auto">
        <label class="form-label small mb-1">Category</label>
        <select name="category_id" class="form-select form-select-sm ts-select">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
    </div>
    @include('partials.warehouse-filter')
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary mt-3">
            <i class="fa fa-filter me-1"></i>Apply
        </button>
    </div>
</form>
</div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card danger">
            <div class="stat-icon danger"><i class="fa fa-money-bill-wave"></i></div>
            <div class="stat-value"><span class="stat-currency">Br</span>{{ number_format($totalAmount,0) }}</div>
            <div class="stat-label">Total Expenses</div>
            <i class="fa fa-money-bill-wave watermark"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa fa-tags"></i></div>
            <div class="stat-value">{{ $byCategory->count() }}</div>
            <div class="stat-label">Categories</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa fa-receipt"></i></div>
            <div class="stat-value">{{ $expenses->total() }}</div>
            <div class="stat-label">Total Records</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">

    {{-- By Category --}}
    <div class="col-12 col-md-5">
        <div class="card h-100">
            <div class="card-header">
                <i class="fa fa-chart-pie me-2 text-danger"></i>By Category
            </div>
            <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr><th>Category</th><th>Count</th><th>Total</th><th>%</th></tr>
                </thead>
                <tbody>
                    @forelse($byCategory as $cat)
                    <tr>
                        <td class="fw-semibold small">{{ $cat->category }}</td>
                        <td class="text-muted small">{{ $cat->count }}</td>
                        <td class="text-danger fw-semibold">Br {{ number_format($cat->total,2) }}</td>
                        <td>
                            @php $pct = $totalAmount > 0 ? round(($cat->total / $totalAmount) * 100, 1) : 0; @endphp
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:5px">
                                    <div class="progress-bar bg-danger" style="width:{{ $pct }}%"></div>
                                </div>
                                <span class="small text-muted">{{ $pct }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">No expenses.</td></tr>
                    @endforelse
                </tbody>
                @if($byCategory->count())
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td>Total</td>
                        <td>{{ $byCategory->sum('count') }}</td>
                        <td class="text-danger">Br {{ number_format($totalAmount,2) }}</td>
                        <td>100%</td>
                    </tr>
                </tfoot>
                @endif
            </table>
            </div>
        </div>
    </div>

    {{-- Expenses List --}}
    <div class="col-12 col-md-7">
        <div class="card h-100">
            <div class="card-header">
                <i class="fa fa-list me-2 text-danger"></i>Expense Records
            </div>
            <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr><th>Ref</th><th>Title</th><th>Category</th><th>Date</th><th>Amount</th></tr>
                </thead>
                <tbody>
                    @forelse($expenses as $exp)
                    <tr>
                        <td>
                            <a href="{{ route('expenses.show', $exp->id) }}"
                               class="small text-primary fw-semibold">
                                {{ $exp->expense_number }}
                            </a>
                        </td>
                        <td>
                            <div class="small fw-medium">{{ $exp->title }}</div>
                            <div class="text-muted" style="font-size:.72rem">
                                {{ $exp->warehouse_name ?? 'Company-wide' }}
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary small">
                                {{ $exp->category_name }}
                            </span>
                        </td>
                        <td class="text-muted small">
                            {{ \Carbon\Carbon::parse($exp->expense_date)->format('M d, Y') }}
                        </td>
                        <td class="text-danger fw-semibold">Br {{ number_format($exp->amount,2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No expenses for this period.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            @if($expenses->hasPages())
                <div class="card-body border-top py-2">{{ $expenses->links() }}</div>
            @endif
        </div>
    </div>

</div>
@endsection
