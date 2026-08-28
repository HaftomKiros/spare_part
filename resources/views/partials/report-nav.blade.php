{{--
    Report sub-navigation bar.
    Include at the top of every report page.
    Usage: @include('partials.report-nav', ['active' => 'sales'])
--}}
@php
    $user = auth()->user();
    $reportLinks = [
        'sales'       => ['route' => 'reports.sales',       'label' => 'Sales',        'icon' => 'fa-chart-line',         'color' => '#6366f1', 'perm' => 'reports.sales'],
        'purchases'   => ['route' => 'reports.purchases',   'label' => 'Purchases',    'icon' => 'fa-truck',              'color' => '#3b82f6', 'perm' => 'reports.purchases'],
        'profit'      => ['route' => 'reports.profit',      'label' => 'Profit',       'icon' => 'fa-sack-dollar',        'color' => '#10b981', 'perm' => 'reports.profit'],
        'stock'       => ['route' => 'reports.stock',       'label' => 'Stock Value',  'icon' => 'fa-boxes-stacked',      'color' => '#8b5cf6', 'perm' => 'reports.stock'],
        'low-stock'   => ['route' => 'reports.low-stock',   'label' => 'Low Stock',    'icon' => 'fa-triangle-exclamation','color' => '#f59e0b', 'perm' => 'reports.low-stock'],
        'spare-parts' => ['route' => 'reports.spare-parts', 'label' => 'Spare Parts',  'icon' => 'fa-gears',              'color' => '#06b6d4', 'perm' => 'reports.spare-parts'],
        'vehicles'    => ['route' => 'reports.vehicles',    'label' => 'Vehicles',     'icon' => 'fa-motorcycle',         'color' => '#f97316', 'perm' => 'reports.vehicles'],
        'expenses'    => ['route' => 'reports.expenses',    'label' => 'Expenses',     'icon' => 'fa-money-bill-wave',    'color' => '#ef4444', 'perm' => 'reports.expenses'],
    ];
@endphp

<div class="report-nav mb-4">
    <div class="report-nav-inner">
        @foreach($reportLinks as $key => $link)
            @if($user->hasPermission($link['perm']))
            <a href="{{ route($link['route']) }}"
               class="report-nav-item {{ ($active ?? '') === $key ? 'active' : '' }}"
               style="{{ ($active ?? '') === $key ? '--rn-color:'.$link['color'] : '' }}">
                <i class="fa-solid {{ $link['icon'] }} report-nav-icon"></i>
                <span class="report-nav-label">{{ $link['label'] }}</span>
                @if($key === 'low-stock' && ($totalStockAlerts ?? 0) > 0)
                    <span class="report-nav-badge">{{ $totalStockAlerts }}</span>
                @endif
            </a>
            @endif
        @endforeach
    </div>
</div>

@once
@push('styles')
<style>
.report-nav {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 6px 8px;
    box-shadow: 0 1px 4px rgba(91,79,207,.05);
    overflow-x: auto;
}
.report-nav-inner {
    display: flex;
    gap: 4px;
    min-width: max-content;
}
.report-nav-item {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 8px 14px;
    border-radius: 10px;
    text-decoration: none;
    font-size: .82rem;
    font-weight: 500;
    color: #64748b;
    transition: background .15s, color .15s;
    white-space: nowrap;
    position: relative;
}
.report-nav-item:hover {
    background: #f1f0fe;
    color: #4f46e5;
}
.report-nav-item.active {
    background: color-mix(in srgb, var(--rn-color, #6366f1) 12%, transparent);
    color: var(--rn-color, #6366f1);
    font-weight: 600;
}
.report-nav-icon {
    font-size: .85rem;
    width: 16px;
    text-align: center;
}
.report-nav-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 16px;
    background: #ef4444;
    color: #fff;
    border-radius: 20px;
    font-size: .62rem;
    font-weight: 700;
    padding: 0 4px;
    margin-left: 2px;
}
/* Report page common styles */
.rpt-filter-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 14px 18px;
    margin-bottom: 1.5rem;
}
.rpt-filter-card .form-label { font-size: .78rem; font-weight: 600; color: #64748b; margin-bottom: 3px; }
.rpt-period-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: #f1f0fe; color: #4f46e5;
    border-radius: 8px; padding: 5px 12px;
    font-size: .78rem; font-weight: 600;
}
</style>
@endpush
@endonce
