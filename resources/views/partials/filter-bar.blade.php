{{--
    Generic filter bar.
    Usage: @include('partials.filter-bar', ['searchPlaceholder' => 'Search...', 'extraFilters' => false])
--}}
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-5 col-lg-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fa fa-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="{{ $searchPlaceholder ?? 'Search...' }}"
                           value="{{ request('search') }}">
                </div>
            </div>

            @if(!empty($extraFilters))
                {{ $extraFilters }}
            @endif

            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fa fa-filter me-1"></i>Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'type', 'date_from', 'date_to', 'category']))
                    <a href="{{ url()->current() }}" class="btn btn-sm btn-outline-secondary ms-1">
                        <i class="fa fa-xmark me-1"></i>Clear
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>
