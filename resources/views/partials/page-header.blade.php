{{--
    Usage:
    @include('partials.page-header', [
        'title'    => 'Spare Parts',
        'subtitle' => 'Manage all spare parts',
        'actions'  => [
            ['label' => 'Add Part', 'route' => 'catalog.spare-parts.create', 'icon' => 'fa-plus', 'class' => 'btn-primary'],
        ]
    ])
--}}
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="page-title">{{ $title }}</h1>
        @if(!empty($subtitle))
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if(!empty($actions))
    <div class="d-flex gap-2 flex-wrap">
        @foreach($actions as $action)
            @if(isset($action['route']))
                <a href="{{ route($action['route'], $action['route_params'] ?? []) }}" class="btn {{ $action['class'] ?? 'btn-primary' }}">
                    @if(!empty($action['icon']))<i class="fa {{ $action['icon'] }} me-1"></i>@endif
                    {{ $action['label'] }}
                </a>
            @elseif(isset($action['url']))
                <a href="{{ $action['url'] }}" class="btn {{ $action['class'] ?? 'btn-primary' }}">
                    @if(!empty($action['icon']))<i class="fa {{ $action['icon'] }} me-1"></i>@endif
                    {{ $action['label'] }}
                </a>
            @endif
        @endforeach
    </div>
    @endif
</div>
