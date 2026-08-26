<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ $company->company_name ?? 'Abush Spare Part' }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
    /* == PROFESSIONAL SIDEBAR ====================================== */

    :root {
        --sb-width: 268px;
        --sb-collapsed: 72px;
        --sb-bg1: #13112a;
        --sb-bg2: #1a1635;
        --sb-accent: #5b4fcf;
        --sb-accent2: #7c6fe0;
        --sb-text: rgba(255,255,255,.72);
        --sb-muted: rgba(255,255,255,.3);
        --sb-hover: rgba(255,255,255,.06);
        --sb-border: rgba(255,255,255,.06);
        --sb-active-from: #5b4fcf;
        --sb-active-to: #7c6fe0;
        --transition: .22s cubic-bezier(.4,0,.2,1);
    }

    body { overflow-x: hidden; }

    /* -- Sidebar shell -- */
    .sidebar {
        position: fixed;
        top: 0; left: 0; bottom: 0;
        width: var(--sb-width);
        background: linear-gradient(180deg, var(--sb-bg1) 0%, var(--sb-bg2) 100%);
        display: flex;
        flex-direction: column;
        z-index: 1050;
        transition: width var(--transition), transform var(--transition);
        box-shadow: 4px 0 32px rgba(0,0,0,.35);
        overflow: visible;
    }

    /* Clip only the inner scroll area, not the whole sidebar */
    .sb-nav {
        overflow-x: hidden;
    }

    body.sb-collapsed .sidebar { width: var(--sb-collapsed); }

    /* -- Brand / Logo -- */
    .sb-brand {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 18px 36px 14px 16px;
        border-bottom: 1px solid var(--sb-border);
        min-height: 70px;
        flex-shrink: 0;
        text-decoration: none;
        overflow: hidden;
    }
    .sb-logo {
        width: 44px; height: 44px;
        background: linear-gradient(135deg, var(--sb-accent), var(--sb-accent2));
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 1.15rem;
        flex-shrink: 0;
        box-shadow: 0 4px 16px rgba(91,79,207,.5);
        transition: box-shadow .3s;
    }
    .sb-brand:hover .sb-logo { box-shadow: 0 6px 24px rgba(91,79,207,.7); }
    .sb-brand-text {
        overflow: hidden;
        transition: opacity var(--transition), width var(--transition);
        min-width: 0;
        flex: 1;
    }
    .sb-name {
        display: block; color: #fff; font-weight: 700;
        font-size: .88rem; white-space: nowrap;
        overflow: hidden; text-overflow: ellipsis;
        letter-spacing: -.01em; line-height: 1.2;
    }
    .sb-tagline {
        display: block; font-size: .67rem;
        color: var(--sb-muted); white-space: nowrap;
        letter-spacing: .04em; text-transform: uppercase;
        margin-top: 2px;
    }
    body.sb-collapsed .sb-brand-text { opacity: 0; width: 0; flex: 0; }

    /* -- Toggle button -- */
    .sb-toggle {
        position: absolute;
        top: 22px; right: -13px;
        width: 26px; height: 26px;
        background: var(--sb-accent);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .65rem;
        cursor: pointer;
        border: 2.5px solid var(--sb-bg1);
        box-shadow: 0 2px 10px rgba(0,0,0,.5);
        transition: background var(--transition), transform var(--transition);
        z-index: 1060;
    }
    .sb-toggle:hover { background: var(--sb-accent2); }
    body.sb-collapsed .sb-toggle { transform: rotate(180deg); }

    /* -- Search -- */
    .sb-search {
        padding: 12px 12px 8px;
        flex-shrink: 0;
        transition: opacity var(--transition);
    }
    .sb-search-inner {
        display: flex; align-items: center; gap: 8px;
        background: rgba(255,255,255,.07);
        border: 1px solid rgba(255,255,255,.08);
        border-radius: 10px;
        padding: 7px 12px;
        transition: background .2s, border-color .2s;
    }
    .sb-search-inner:focus-within {
        background: rgba(255,255,255,.1);
        border-color: var(--sb-accent);
    }
    .sb-search-inner i { color: var(--sb-muted); font-size: .8rem; flex-shrink: 0; }
    .sb-search-inner input {
        background: none; border: none; outline: none;
        color: #fff; font-size: .82rem; width: 100%;
        font-family: inherit;
    }
    .sb-search-inner input::placeholder { color: var(--sb-muted); }
    body.sb-collapsed .sb-search { opacity: 0; pointer-events: none; padding: 0; height: 0; }

    /* -- Nav scroll area -- */
    .sb-nav {
        flex: 1; overflow-y: auto; overflow-x: hidden;
        padding: 6px 0 20px;
        scrollbar-width: thin;
        scrollbar-color: rgba(255,255,255,.06) transparent;
    }
    .sb-nav::-webkit-scrollbar { width: 4px; }
    .sb-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.08); border-radius: 4px; }

    /* -- Section group -- */
    .sb-group { margin-bottom: 4px; }

    .sb-group-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 8px 16px 4px;
        cursor: pointer;
        transition: opacity .2s;
    }
    .sb-group-title {
        font-size: .62rem; font-weight: 700;
        letter-spacing: .1em; text-transform: uppercase;
        color: var(--sb-muted);
        white-space: nowrap;
        transition: opacity var(--transition);
    }
    .sb-group-arrow {
        color: var(--sb-muted); font-size: .6rem;
        transition: transform var(--transition);
        flex-shrink: 0;
    }
    .sb-group.collapsed .sb-group-arrow { transform: rotate(-90deg); }

    body.sb-collapsed .sb-group-title,
    body.sb-collapsed .sb-group-arrow { opacity: 0; }

    .sb-group-items {
        overflow: hidden;
        transition: max-height .3s cubic-bezier(.4,0,.2,1);
        max-height: 600px;
    }
    .sb-group.collapsed .sb-group-items { max-height: 0; }
    body.sb-collapsed .sb-group.collapsed .sb-group-items { max-height: 600px; }

    /* -- Nav item -- */
    .sb-item {
        display: flex; align-items: center; gap: 11px;
        padding: 9px 12px 9px 14px;
        margin: 1px 8px;
        border-radius: 10px;
        color: var(--sb-text);
        font-size: .845rem; font-weight: 450;
        white-space: nowrap;
        text-decoration: none;
        position: relative;
        transition: background var(--transition), color var(--transition), transform .15s;
        cursor: pointer;
    }
    .sb-item:hover {
        background: var(--sb-hover);
        color: #fff;
        transform: translateX(2px);
    }
    .sb-item.active {
        background: linear-gradient(135deg, var(--sb-active-from), var(--sb-active-to));
        color: #fff;
        font-weight: 600;
        box-shadow: 0 4px 16px rgba(91,79,207,.35);
        transform: none;
    }

    /* Active left indicator bar */
    .sb-item.active::before {
        content: '';
        position: absolute;
        left: -8px; top: 50%;
        transform: translateY(-50%);
        width: 3px; height: 60%;
        background: #fff;
        border-radius: 0 3px 3px 0;
        opacity: .6;
    }

    .sb-icon {
        width: 22px; height: 22px;
        display: flex; align-items: center; justify-content: center;
        font-size: .9rem; flex-shrink: 0;
    }

    .sb-label { flex: 1; }

    /* Badge on nav item */
    .sb-badge {
        font-size: .64rem; font-weight: 700;
        padding: 2px 7px;
        border-radius: 20px;
        background: rgba(239,68,68,.85);
        color: #fff;
        flex-shrink: 0;
    }
    .sb-badge.warn { background: rgba(217,119,6,.9); }

    /* New Sale / New Purchase highlight pill */
    .sb-item.sb-cta {
        background: rgba(91,79,207,.18);
        border: 1px solid rgba(91,79,207,.25);
        color: #c4b5fd;
    }
    .sb-item.sb-cta:hover {
        background: rgba(91,79,207,.28);
        color: #fff;
    }
    .sb-item.sb-cta.active {
        background: linear-gradient(135deg, var(--sb-active-from), var(--sb-active-to));
        border-color: transparent;
        color: #fff;
    }

    /* Collapsed tooltip on hover */
    body.sb-collapsed .sb-item .sb-label,
    body.sb-collapsed .sb-item .sb-badge { display: none; }
    body.sb-collapsed .sb-item {
        justify-content: center;
        padding: 11px;
        margin: 2px 8px;
        position: relative;
    }
    body.sb-collapsed .sb-item::after {
        content: attr(data-tooltip);
        position: absolute;
        left: calc(100% + 12px);
        top: 50%; transform: translateY(-50%);
        background: #1c1735;
        color: #fff;
        font-size: .78rem;
        font-weight: 500;
        padding: 5px 12px;
        border-radius: 8px;
        white-space: nowrap;
        pointer-events: none;
        opacity: 0;
        transition: opacity .18s;
        border: 1px solid rgba(255,255,255,.1);
        box-shadow: 0 4px 16px rgba(0,0,0,.4);
        z-index: 9999;
    }
    body.sb-collapsed .sb-item:hover::after { opacity: 1; }
    body.sb-collapsed .sb-item.active::before { display: none; }

    /* -- Divider -- */
    .sb-divider {
        height: 1px;
        background: var(--sb-border);
        margin: 8px 16px;
    }

    /* -- User footer -- */
    .sb-user {
        flex-shrink: 0;
        border-top: 1px solid var(--sb-border);
        padding: 12px;
        display: flex; align-items: center; gap: 10px;
        cursor: pointer;
        transition: background .2s;
        position: relative;
    }
    .sb-user:hover { background: var(--sb-hover); }
    .sb-avatar {
        width: 38px; height: 38px;
        border-radius: 10px;
        object-fit: cover;
        flex-shrink: 0;
        border: 2px solid rgba(255,255,255,.12);
    }
    .sb-user-info { flex: 1; overflow: hidden; transition: opacity var(--transition); }
    .sb-user-name {
        display: block; color: #fff;
        font-size: .82rem; font-weight: 600;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .sb-user-role {
        display: block; color: var(--sb-muted);
        font-size: .68rem; white-space: nowrap;
    }
    .sb-user-chevron { color: var(--sb-muted); font-size: .7rem; flex-shrink: 0; transition: opacity var(--transition); }
    body.sb-collapsed .sb-user-info,
    body.sb-collapsed .sb-user-chevron { opacity: 0; width: 0; overflow: hidden; }
    body.sb-collapsed .sb-user { justify-content: center; }

    /* User dropdown popup */
    .sb-user-dropdown {
        position: absolute;
        bottom: calc(100% + 8px);
        left: 12px; right: 12px;
        background: #1c1735;
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 12px;
        padding: 6px;
        box-shadow: 0 8px 32px rgba(0,0,0,.5);
        display: none;
        z-index: 9999;
    }
    body.sb-collapsed .sb-user-dropdown { left: calc(100% + 8px); bottom: 8px; right: auto; width: 180px; }
    .sb-user-dropdown.show { display: block; }
    .sb-user-dropdown a,
    .sb-user-dropdown button {
        display: flex; align-items: center; gap: 9px;
        padding: 9px 12px;
        border-radius: 8px;
        color: var(--sb-text);
        font-size: .83rem;
        text-decoration: none;
        background: none; border: none; width: 100%;
        cursor: pointer; font-family: inherit;
        transition: background .15s, color .15s;
    }
    .sb-user-dropdown a:hover { background: var(--sb-hover); color: #fff; }
    .sb-user-dropdown button:hover { background: rgba(239,68,68,.15); color: #f87171; }
    .sb-user-dropdown .divider { height: 1px; background: var(--sb-border); margin: 4px 0; }

    /* == TOP NAVBAR =============================================== */
    #content-wrapper {
        margin-left: var(--sb-width);
        min-height: 100vh;
        display: flex; flex-direction: column;
        transition: margin-left var(--transition);
    }
    body.sb-collapsed #content-wrapper { margin-left: var(--sb-collapsed); }

    .top-navbar {
        position: sticky; top: 0;
        height: 64px;
        background: var(--card-bg);
        border-bottom: 1px solid var(--border-color);
        display: flex; align-items: center;
        justify-content: space-between;
        padding: 0 24px; z-index: 900;
        box-shadow: 0 1px 4px rgba(91,79,207,.06);
        flex-shrink: 0;
    }

    /* == RESPONSIVE =============================================== */
    @media (max-width: 991.98px) {
        .sidebar {
            transform: translateX(-100%);
            width: var(--sb-width) !important;
        }
        body.sb-mobile-open .sidebar { transform: translateX(0); }
        #content-wrapper { margin-left: 0 !important; }
        .sb-toggle { display: none; }
        body.sb-collapsed .sb-item::after { display: none; }
    }
    </style>
    @stack('styles')
</head>
<body>

{{-- == PAGE LOADER ============================================== --}}
<div id="pageLoader" style="display:none">
    <div class="loader-inner">
        <div class="loader-spinner"></div>
        <div class="loader-text">Loading...</div>
    </div>
</div>

{{-- == SIDEBAR ================================================== --}}
<nav class="sidebar" id="sidebar">

    {{-- Toggle button --}}
    <div class="sb-toggle d-none d-lg-flex" id="sbToggle" title="Toggle sidebar">
        <i class="fa fa-chevron-left"></i>
    </div>

    {{-- Brand --}}
    <a href="{{ route('dashboard') }}" class="sb-brand">
        <div class="sb-logo">
            <i class="fa-solid fa-motorcycle"></i>
        </div>
        <div class="sb-brand-text">
            <span class="sb-name">{{ $company->company_name ?? 'Abush Spare Part' }}</span>
            <span class="sb-tagline">Inventory System</span>
        </div>
    </a>

    {{-- Search --}}
    <div class="sb-search">
        <div class="sb-search-inner">
            <i class="fa fa-magnifying-glass"></i>
            <input type="text" id="sidebarSearch" placeholder="Quick search…" autocomplete="off">
        </div>
    </div>

    {{-- Navigation --}}
    <div class="sb-nav" id="sidebarNav">

        {{-- Dashboard --}}
        <div class="sb-group">
            <a href="{{ route('dashboard') }}"
               class="sb-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
               data-tooltip="Dashboard">
                <span class="sb-icon"><i class="fa-solid fa-tachometer-alt"></i></span>
                <span class="sb-label">Dashboard</span>
            </a>
        </div>

        <div class="sb-divider"></div>

        {{-- CATALOG --}}
        <div class="sb-group" id="grp-catalog">
            <div class="sb-group-header" onclick="toggleGroup('grp-catalog')">
                <span class="sb-group-title">Catalog</span>
                <i class="fa fa-chevron-down sb-group-arrow"></i>
            </div>
            <div class="sb-group-items">
                <a href="{{ route('catalog.vehicle-types.index') }}"
                   class="sb-item {{ request()->routeIs('catalog.vehicle-types*') ? 'active' : '' }}"
                   data-tooltip="Vehicle Types">
                    <span class="sb-icon"><i class="fa-solid fa-motorcycle"></i></span>
                    <span class="sb-label">Vehicle Types</span>
                </a>
                <a href="{{ route('catalog.vehicle-models.index') }}"
                   class="sb-item {{ request()->routeIs('catalog.vehicle-models*') ? 'active' : '' }}"
                   data-tooltip="Vehicle Models">
                    <span class="sb-icon"><i class="fa-solid fa-car-side"></i></span>
                    <span class="sb-label">Vehicle Models</span>
                </a>
                <a href="{{ route('catalog.part-categories.index') }}"
                   class="sb-item {{ request()->routeIs('catalog.part-categories*') ? 'active' : '' }}"
                   data-tooltip="Part Categories">
                    <span class="sb-icon"><i class="fa-solid fa-layer-group"></i></span>
                    <span class="sb-label">Part Categories</span>
                </a>
                <a href="{{ route('catalog.spare-parts.index') }}"
                   class="sb-item {{ request()->routeIs('catalog.spare-parts*') ? 'active' : '' }}"
                   data-tooltip="Spare Parts">
                    <span class="sb-icon"><i class="fa-solid fa-gears"></i></span>
                    <span class="sb-label">Spare Parts</span>
                </a>
                <a href="{{ route('catalog.units.index') }}"
                   class="sb-item {{ request()->routeIs('catalog.units*') ? 'active' : '' }}"
                   data-tooltip="Units">
                    <span class="sb-icon"><i class="fa-solid fa-ruler"></i></span>
                    <span class="sb-label">Units</span>
                </a>
            </div>
        </div>

        <div class="sb-divider"></div>

        {{-- INVENTORY --}}
        <div class="sb-group" id="grp-inventory">
            <div class="sb-group-header" onclick="toggleGroup('grp-inventory')">
                <span class="sb-group-title">Inventory</span>
                <i class="fa fa-chevron-down sb-group-arrow"></i>
            </div>
            <div class="sb-group-items">
                <a href="{{ route('inventory.transfers.index') }}"
                   class="sb-item {{ request()->routeIs('inventory.transfers*') ? 'active' : '' }}"
                   data-tooltip="Stock Transfer">
                    <span class="sb-icon" style="color:#f59e0b"><i class="fa-solid fa-right-left"></i></span>
                    <span class="sb-label">Stock Transfer</span>
                </a>
                <a href="{{ route('inventory.stock-in.index') }}"
                   class="sb-item {{ request()->routeIs('inventory.stock-in*') ? 'active' : '' }}"
                   data-tooltip="Stock Entry">
                    <span class="sb-icon" style="color:#34d399"><i class="fa-solid fa-download"></i></span>
                    <span class="sb-label">Stock Entry</span>
                </a>
                <a href="{{ route('inventory.adjustments.index') }}"
                   class="sb-item {{ request()->routeIs('inventory.adjustments*') ? 'active' : '' }}"
                   data-tooltip="Adjustments">
                    <span class="sb-icon"><i class="fa-solid fa-sliders"></i></span>
                    <span class="sb-label">Adjustments</span>
                </a>
                <a href="{{ route('inventory.current-stock') }}"
                   class="sb-item {{ request()->routeIs('inventory.current-stock*') ? 'active' : '' }}"
                   data-tooltip="Current Stock">
                    <span class="sb-icon"><i class="fa-solid fa-warehouse"></i></span>
                    <span class="sb-label">Current Stock</span>
                </a>
                <a href="{{ route('inventory.history') }}"
                   class="sb-item {{ request()->routeIs('inventory.history*') ? 'active' : '' }}"
                   data-tooltip="History">
                    <span class="sb-icon"><i class="fa-solid fa-history"></i></span>
                    <span class="sb-label">History</span>
                </a>
            </div>
        </div>

        <div class="sb-divider"></div>

        {{-- SALES --}}
        <div class="sb-group" id="grp-sales">
            <div class="sb-group-header" onclick="toggleGroup('grp-sales')">
                <span class="sb-group-title">Sales</span>
                <i class="fa fa-chevron-down sb-group-arrow"></i>
            </div>
            <div class="sb-group-items">
                <a href="{{ route('sales.create') }}"
                   class="sb-item sb-cta {{ request()->routeIs('sales.create') ? 'active' : '' }}"
                   data-tooltip="New Sale">
                    <span class="sb-icon"><i class="fa-solid fa-plus-circle"></i></span>
                    <span class="sb-label">New Sale</span>
                </a>
                <a href="{{ route('sales.index') }}"
                   class="sb-item {{ request()->routeIs('sales.index') || request()->routeIs('sales.show*') ? 'active' : '' }}"
                   data-tooltip="Sales History">
                    <span class="sb-icon"><i class="fa-solid fa-receipt"></i></span>
                    <span class="sb-label">Sales History</span>
                </a>
                <a href="{{ route('sales.returns.index') }}"
                   class="sb-item {{ request()->routeIs('sales.returns*') ? 'active' : '' }}"
                   data-tooltip="Returns">
                    <span class="sb-icon"><i class="fa-solid fa-undo"></i></span>
                    <span class="sb-label">Returns</span>
                </a>
                <a href="{{ route('sales.customers.index') }}"
                   class="sb-item {{ request()->routeIs('sales.customers*') ? 'active' : '' }}"
                   data-tooltip="Customers">
                    <span class="sb-icon"><i class="fa-solid fa-users"></i></span>
                    <span class="sb-label">Customers</span>
                </a>
            </div>
        </div>

        <div class="sb-divider"></div>

        {{-- PURCHASES --}}
        <div class="sb-group" id="grp-purchases">
            <div class="sb-group-header" onclick="toggleGroup('grp-purchases')">
                <span class="sb-group-title">Purchases</span>
                <i class="fa fa-chevron-down sb-group-arrow"></i>
            </div>
            <div class="sb-group-items">
                <a href="{{ route('purchases.create') }}"
                   class="sb-item sb-cta {{ request()->routeIs('purchases.create') ? 'active' : '' }}"
                   data-tooltip="New Purchase">
                    <span class="sb-icon"><i class="fa-solid fa-file-invoice"></i></span>
                    <span class="sb-label">New Purchase</span>
                </a>
                <a href="{{ route('purchases.index') }}"
                   class="sb-item {{ request()->routeIs('purchases.index') || request()->routeIs('purchases.show*') ? 'active' : '' }}"
                   data-tooltip="Purchase History">
                    <span class="sb-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
                    <span class="sb-label">Purchase History</span>
                </a>
                <a href="{{ route('purchases.suppliers.index') }}"
                   class="sb-item {{ request()->routeIs('purchases.suppliers*') ? 'active' : '' }}"
                   data-tooltip="Suppliers">
                    <span class="sb-icon"><i class="fa-solid fa-truck"></i></span>
                    <span class="sb-label">Suppliers</span>
                </a>
            </div>
        </div>

        <div class="sb-divider"></div>

        {{-- REPORTS --}}
        <div class="sb-group" id="grp-reports">
            <div class="sb-group-header" onclick="toggleGroup('grp-reports')">
                <span class="sb-group-title">Reports</span>
                <i class="fa fa-chevron-down sb-group-arrow"></i>
            </div>
            <div class="sb-group-items">
                <a href="{{ route('reports.sales') }}"
                   class="sb-item {{ request()->routeIs('reports.sales') ? 'active' : '' }}"
                   data-tooltip="Sales Report">
                    <span class="sb-icon"><i class="fa-solid fa-chart-line"></i></span>
                    <span class="sb-label">Sales</span>
                </a>
                <a href="{{ route('reports.vehicles') }}"
                   class="sb-item {{ request()->routeIs('reports.vehicles') ? 'active' : '' }}"
                   data-tooltip="Vehicles Report">
                    <span class="sb-icon"><i class="fa-solid fa-motorcycle"></i></span>
                    <span class="sb-label">Vehicles</span>
                </a>
                <a href="{{ route('reports.spare-parts') }}"
                   class="sb-item {{ request()->routeIs('reports.spare-parts') ? 'active' : '' }}"
                   data-tooltip="Spare Parts Report">
                    <span class="sb-icon"><i class="fa-solid fa-gears"></i></span>
                    <span class="sb-label">Spare Parts</span>
                </a>
                <a href="{{ route('reports.stock') }}"
                   class="sb-item {{ request()->routeIs('reports.stock') ? 'active' : '' }}"
                   data-tooltip="Stock Report">
                    <span class="sb-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
                    <span class="sb-label">Stock</span>
                </a>
                <a href="{{ route('reports.low-stock') }}"
                   class="sb-item {{ request()->routeIs('reports.low-stock') ? 'active' : '' }}"
                   data-tooltip="Low Stock">
                    <span class="sb-icon" style="color:#fbbf24"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <span class="sb-label">Low Stock</span>
                    @if($lowCount > 0)
                        <span class="sb-badge warn">{{ $lowCount }}</span>
                    @endif
                </a>
                <a href="{{ route('reports.purchases') }}"
                   class="sb-item {{ request()->routeIs('reports.purchases') ? 'active' : '' }}"
                   data-tooltip="Purchases Report">
                    <span class="sb-icon"><i class="fa-solid fa-truck-loading"></i></span>
                    <span class="sb-label">Purchases</span>
                </a>
                <a href="{{ route('reports.profit') }}"
                   class="sb-item {{ request()->routeIs('reports.profit') ? 'active' : '' }}"
                   data-tooltip="Profit Report">
                    <span class="sb-icon" style="color:#34d399"><i class="fa-solid fa-coins"></i></span>
                    <span class="sb-label">Profit</span>
                </a>
            </div>
        </div>

        <div class="sb-divider"></div>

        {{-- SETTINGS --}}
        <div class="sb-group" id="grp-settings">
            <div class="sb-group-header" onclick="toggleGroup('grp-settings')">
                <span class="sb-group-title">Settings</span>
                <i class="fa fa-chevron-down sb-group-arrow"></i>
            </div>
            <div class="sb-group-items">
                <a href="{{ route('settings.company') }}"
                   class="sb-item {{ request()->routeIs('settings.company') ? 'active' : '' }}"
                   data-tooltip="Company">
                    <span class="sb-icon"><i class="fa-solid fa-building"></i></span>
                    <span class="sb-label">Company</span>
                </a>
                <a href="{{ route('settings.users.index') }}"
                   class="sb-item {{ request()->routeIs('settings.users*') ? 'active' : '' }}"
                   data-tooltip="Users">
                    <span class="sb-icon"><i class="fa-solid fa-user-cog"></i></span>
                    <span class="sb-label">Users</span>
                </a>
                <a href="{{ route('settings.roles.index') }}"
                   class="sb-item {{ request()->routeIs('settings.roles*') ? 'active' : '' }}"
                   data-tooltip="Roles">
                    <span class="sb-icon"><i class="fa-solid fa-shield-alt"></i></span>
                    <span class="sb-label">Roles</span>
                </a>
                <a href="{{ route('settings.warehouses.index') }}"
                   class="sb-item {{ request()->routeIs('settings.warehouses*') ? 'active' : '' }}"
                   data-tooltip="Warehouses">
                    <span class="sb-icon"><i class="fa-solid fa-warehouse"></i></span>
                    <span class="sb-label">Warehouses</span>
                </a>
            </div>
        </div>

    </div>{{-- /sb-nav --}}

    {{-- User Footer --}}
    <div class="sb-user" id="sbUser" onclick="toggleUserMenu()">
        <img src="{{ auth()->user()->avatar_url }}" class="sb-avatar" alt="">
        <div class="sb-user-info">
            <span class="sb-user-name">{{ auth()->user()->name }}</span>
            <span class="sb-user-role">{{ auth()->user()->role?->display_name ?? 'User' }}</span>
        </div>
        <i class="fa fa-ellipsis-vertical sb-user-chevron"></i>

        {{-- User popup --}}
        <div class="sb-user-dropdown" id="sbUserDropdown">
            <a href="{{ route('settings.users.edit', auth()->id()) }}">
                <i class="fa fa-user-pen"></i> My Profile
            </a>
            <a href="{{ route('settings.company') }}">
                <i class="fa fa-building"></i> Company Settings
            </a>
            <div class="divider"></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">
                    <i class="fa fa-sign-out-alt"></i> Sign Out
                </button>
            </form>
        </div>
    </div>

</nav>

{{-- Mobile overlay --}}
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeMobileSidebar()"></div>

{{-- == MAIN WRAPPER ============================================== --}}
<div id="content-wrapper">

    {{-- Top Navbar --}}
    <nav class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-icon text-muted d-lg-none" id="mobileMenuBtn" onclick="openMobileSidebar()">
                <i class="fa-solid fa-bars fs-5"></i>
            </button>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0" style="font-size:.83rem">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}" style="color:var(--brand-1)">
                            <i class="fa fa-house me-1"></i>Home
                        </a>
                    </li>
                    @yield('breadcrumb')
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2">
            {{-- Page title on mobile --}}
            <span class="d-md-none fw-semibold text-dark" style="font-size:.9rem">@yield('title','Dashboard')</span>

            {{-- Low stock bell --}}
            @if(isset($lowCount) && $lowCount > 0)
            <a href="{{ route('reports.low-stock') }}"
               class="btn btn-icon position-relative text-muted" title="Low Stock Alert">
                <i class="fa-solid fa-bell fs-5"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                      style="font-size:.6rem;padding:3px 5px">{{ $lowCount }}</span>
            </a>
            @endif

            {{-- User avatar (desktop) --}}
            <div class="dropdown d-none d-md-block">
                <button class="btn btn-icon d-flex align-items-center gap-2 px-2 text-muted"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="{{ auth()->user()->avatar_url }}"
                         class="rounded-circle" width="34" height="34"
                         style="border:2px solid var(--border-color)" alt="">
                    <div class="text-start d-none d-lg-block">
                        <div class="fw-semibold text-dark" style="font-size:.82rem;line-height:1.2">{{ auth()->user()->name }}</div>
                        <div class="text-muted" style="font-size:.7rem">{{ auth()->user()->role?->display_name ?? 'User' }}</div>
                    </div>
                    <i class="fa fa-chevron-down text-muted" style="font-size:.65rem"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius:12px;min-width:190px;padding:6px">
                    <li class="px-3 py-2 border-bottom mb-1">
                        <div class="fw-semibold" style="font-size:.83rem">{{ auth()->user()->name }}</div>
                        <div class="text-muted" style="font-size:.75rem">{{ auth()->user()->email }}</div>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-2" href="{{ route('settings.users.edit', auth()->id()) }}"
                           style="font-size:.84rem;padding:8px 12px">
                            <i class="fa fa-user-pen me-2 text-muted" style="width:16px"></i>My Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-2" href="{{ route('settings.company') }}"
                           style="font-size:.84rem;padding:8px 12px">
                            <i class="fa fa-building me-2 text-muted" style="width:16px"></i>Settings
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item rounded-2 text-danger"
                                    style="font-size:.84rem;padding:8px 12px" type="submit">
                                <i class="fa fa-sign-out-alt me-2" style="width:16px"></i>Sign Out
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    {{-- Page Content --}}
    <main class="main-content">

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert"
             style="border-left:4px solid #059669">
            <i class="fa fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert"
             style="border-left:4px solid #dc2626">
            <i class="fa fa-circle-xmark me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert"
             style="border-left:4px solid #d97706">
            <i class="fa fa-triangle-exclamation me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @yield('content')
    </main>

    <footer class="main-footer">
        <span class="text-muted">&copy; {{ date('Y') }} {{ $company->company_name ?? 'Abush Spare Part' }}. All rights reserved.</span>
        <span class="ms-auto text-muted small">v1.0.0</span>
    </footer>>

</div>

{{-- == SCRIPTS ================================================== --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
// Page loader - only show when navigating AWAY, never on initial load
(function () {
    var loader = document.getElementById('pageLoader');
    if (!loader) return;

    // Always hide immediately on page load/refresh
    loader.style.display = 'none';

    // Show only when user clicks a link (navigating away)
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[href]');
        if (!link) return;
        var href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript') ||
            link.target === '_blank' || e.ctrlKey || e.metaKey || e.shiftKey ||
            link.dataset.deleteUrl || link.dataset.bsToggle) return;
        loader.style.display = 'flex';
        loader.classList.remove('hide');
    });

    // Show when submitting a form (page navigation)
    document.addEventListener('submit', function (e) {
        if (e.target.dataset.ajax) return;
        loader.style.display = 'flex';
        loader.classList.remove('hide');
    });

    // Safety: hide after 8 seconds in case something gets stuck
    setTimeout(function () {
        loader.style.display = 'none';
    }, 8000);
})();
</script>
<script src="{{ asset('js/app.js') }}"></script>

<script>
// -- Sidebar collapse (desktop) --------------------------------
const sbToggle  = document.getElementById('sbToggle');
const body      = document.body;

sbToggle?.addEventListener('click', () => {
    body.classList.toggle('sb-collapsed');
    // only save collapsed state, remove key when expanded
    if (body.classList.contains('sb-collapsed')) {
        localStorage.setItem('sbCollapsed', 'true');
    } else {
        localStorage.removeItem('sbCollapsed');
    }
});

// Restore state - default is EXPANDED
// Clear any old collapsed state so sidebar always starts expanded
localStorage.removeItem('sbCollapsed');
localStorage.removeItem('sbGroups');
// Only collapse if user explicitly clicked toggle this session


// -- Mobile sidebar --------------------------------------------
function openMobileSidebar() {
    body.classList.add('sb-mobile-open');
    document.getElementById('sidebarOverlay').style.display = 'block';
}
function closeMobileSidebar() {
    body.classList.remove('sb-mobile-open');
    document.getElementById('sidebarOverlay').style.display = 'none';
}

// -- Collapsible nav groups ------------------------------------
function toggleGroup(id) {
    if (body.classList.contains('sb-collapsed')) return;
    const grp = document.getElementById(id);
    grp.classList.toggle('collapsed');
}

// Restore group states - all expanded by default
(function restoreGroups() {
    // Don't restore any collapsed state, just ensure active group stays open
    document.querySelectorAll('.sb-group').forEach(grp => {
        grp.classList.remove('collapsed');
    });
})();

// -- User popup ------------------------------------------------
function toggleUserMenu() {
    document.getElementById('sbUserDropdown').classList.toggle('show');
}
document.addEventListener('click', e => {
    if (!e.target.closest('#sbUser')) {
        document.getElementById('sbUserDropdown')?.classList.remove('show');
    }
});

// -- Sidebar search --------------------------------------------
document.getElementById('sidebarSearch')?.addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.sb-item').forEach(item => {
        const txt = item.textContent.toLowerCase();
        item.style.display = q && !txt.includes(q) ? 'none' : '';
    });
    document.querySelectorAll('.sb-group').forEach(grp => {
        grp.classList.remove('collapsed');
    });
});

// -- Auto-dismiss flash alerts --------------------------------
document.querySelectorAll('.alert-success').forEach(el => {
    setTimeout(() => bootstrap.Alert.getOrCreateInstance(el)?.close(), 4000);
});

// -- Confirm delete --------------------------------------------
document.querySelectorAll('[data-delete-url]').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('deleteForm').action = this.dataset.deleteUrl;
        document.getElementById('deleteModalMessage').textContent =
            this.dataset.deleteMessage || 'Are you sure you want to delete this record?';
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });
});
</script>

@stack('scripts')

<script>
// GLOBAL UX - Tom Select + Live Search + Auto-submit
document.addEventListener('DOMContentLoaded', function () {

    // Tom Select: initialise every .ts-select (skip dashboard warehouse which self-inits)
    document.querySelectorAll('select.ts-select:not(.ts-dashboard-wh)').forEach(function (el) {
        if (el._tomSelect) return;
        var isFilter = !!el.closest('.filter-form');
        var opts = {
            allowEmptyOption: true,
            placeholder: el.dataset.placeholder || (el.options[0] ? el.options[0].text : 'Search...'),
            maxOptions: 500,
        };
        if (isFilter) {
            opts.onChange = function () {
                var form = el.closest('form');
                if (form) form.submit();
            };
        }
        new TomSelect(el, opts);
    });

    // Live search: debounce .live-search inputs 350ms
    document.querySelectorAll('input.live-search').forEach(function (input) {
        var timer;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                var form = input.closest('form');
                if (form) form.submit();
            }, 350);
        });
    });

    // Auto-submit plain filter selects without ts-select
    document.querySelectorAll('.filter-form select:not(.ts-select)').forEach(function (sel) {
        if (sel._tomSelect) return;
        sel.addEventListener('change', function () {
            var form = sel.closest('form');
            if (form) form.submit();
        });
    });

});
</script>
</body>
</html>
