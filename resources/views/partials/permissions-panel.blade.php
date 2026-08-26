{{--
    Collapsible permissions panel.
    Usage:
        @include('partials.permissions-panel', [
            'permissions' => $permissions,        // from RoleController::PERMISSION_LIST
            'selected'    => old('permissions', $role->permissions ?? []),
        ])
--}}
@php
use App\Http\Controllers\Settings\RoleController;

// Group permissions by their top-level module prefix
$groups = [];
foreach ($permissions as $key => $label) {
    if ($key === 'all') {
        $groups['__admin'][$key] = $label;
        continue;
    }
    // e.g. 'catalog.vehicle-types.view' → group 'catalog', sub-group 'vehicle-types'
    $parts     = explode('.', $key);
    $module    = $parts[0];                                  // catalog / inventory / sales / ...
    $resource  = isset($parts[2]) ? $parts[0].'.'.$parts[1] : $parts[0]; // catalog.vehicle-types
    $groups[$module][$resource][$key] = $label;
}

$moduleLabels = [
    'catalog'   => ['icon' => 'fa-book-open',      'color' => '#5b4fcf', 'label' => 'Catalog'],
    'inventory' => ['icon' => 'fa-warehouse',       'color' => '#059669', 'label' => 'Inventory'],
    'sales'     => ['icon' => 'fa-receipt',         'color' => '#0284c7', 'label' => 'Sales'],
    'purchases' => ['icon' => 'fa-truck',           'color' => '#d97706', 'label' => 'Purchases'],
    'reports'   => ['icon' => 'fa-chart-line',      'color' => '#7c3aed', 'label' => 'Reports'],
    'settings'  => ['icon' => 'fa-gear',            'color' => '#dc2626', 'label' => 'Settings'],
    '__admin'   => ['icon' => 'fa-shield-halved',   'color' => '#111827', 'label' => 'Admin'],
];

// Clean resource name for display: 'catalog.vehicle-types' → 'Vehicle Types'
function resourceLabel(string $resource): string {
    $parts = explode('.', $resource);
    $last  = end($parts);
    return ucwords(str_replace('-', ' ', $last));
}
@endphp

<div class="mb-3">
    <div class="divider-label">Permissions</div>
    <div class="text-muted small mb-3">Click a section header to expand / collapse it.</div>
</div>

<div class="permission-groups" id="permGroups">
@foreach($groups as $module => $resources)
    @php $meta = $moduleLabels[$module] ?? ['icon'=>'fa-circle','color'=>'#6b7280','label'=>ucfirst($module)]; @endphp

    {{-- Module accordion header --}}
    <div class="perm-module mb-2" id="pmod-{{ $module }}">
        <button type="button"
                class="perm-module-header w-100 d-flex align-items-center gap-3 p-3 rounded-3 border-0 text-start"
                style="background:var(--surface-2,#f8f9fa);cursor:pointer"
                onclick="togglePermModule('{{ $module }}')"
                aria-expanded="false">
            <span style="width:32px;height:32px;border-radius:8px;background:{{ $meta['color'] }}20;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="fa {{ $meta['icon'] }}" style="color:{{ $meta['color'] }};font-size:.85rem"></i>
            </span>
            <span class="fw-semibold" style="color:{{ $meta['color'] }};flex:1">{{ $meta['label'] }}</span>
            {{-- Checked count badge --}}
            <span class="perm-count-badge badge rounded-pill"
                  style="background:{{ $meta['color'] }}20;color:{{ $meta['color'] }};font-size:.72rem;min-width:26px"
                  id="badge-{{ $module }}">0</span>
            <i class="fa fa-chevron-down perm-chevron ms-1" style="font-size:.75rem;color:#9ca3af;transition:transform .2s" id="chev-{{ $module }}"></i>
        </button>

        {{-- Collapsible body --}}
        <div class="perm-module-body" id="pbody-{{ $module }}" style="display:none;padding:12px 4px 4px">
            {{-- Select / Deselect all for this module --}}
            <div class="d-flex justify-content-end gap-2 mb-2">
                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2"
                        style="font-size:.75rem"
                        onclick="toggleModulePerms('{{ $module }}', true)">
                    <i class="fa fa-check-double me-1"></i>All
                </button>
                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2"
                        style="font-size:.75rem"
                        onclick="toggleModulePerms('{{ $module }}', false)">
                    <i class="fa fa-xmark me-1"></i>None
                </button>
            </div>

            @foreach($resources as $resource => $keys)
                @if($module === '__admin')
                    {{-- Admin permission has no sub-group --}}
                    @foreach($keys as $key => $label)
                    <div class="form-check mb-2 ms-1">
                        <input class="form-check-input perm-cb pmod-{{ $module }}"
                               type="checkbox" name="permissions[]"
                               value="{{ $key }}" id="perm_{{ $loop->parent->index }}_{{ $loop->index }}"
                               {{ in_array($key, $selected) ? 'checked' : '' }}
                               onchange="updateBadge('{{ $module }}')">
                        <label class="form-check-label small fw-semibold text-dark" for="perm_{{ $loop->parent->index }}_{{ $loop->index }}">
                            {{ $label }}
                        </label>
                    </div>
                    @endforeach
                @else
                    {{-- Resource sub-group --}}
                    <div class="perm-resource mb-3">
                        <div class="d-flex align-items-center gap-2 mb-1 pb-1" style="border-bottom:1px dashed #e5e7eb">
                            <span class="small fw-semibold text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.04em">
                                {{ resourceLabel($resource) }}
                            </span>
                            {{-- Select all for this resource --}}
                            <button type="button" class="btn btn-xs btn-link text-muted p-0 ms-auto"
                                    style="font-size:.7rem"
                                    onclick="toggleResourcePerms('{{ $resource }}', true)">all</button>
                            <span style="color:#d1d5db">|</span>
                            <button type="button" class="btn btn-xs btn-link text-muted p-0"
                                    style="font-size:.7rem"
                                    onclick="toggleResourcePerms('{{ $resource }}', false)">none</button>
                        </div>
                        <div class="row g-1">
                            @foreach($keys as $key => $label)
                            @php
                                // Extract the action part: 'catalog.vehicle-types.view' → 'View'
                                $parts  = explode('.', $key);
                                $action = ucfirst(end($parts));
                                $actionColors = [
                                    'View'   => '#0284c7',
                                    'Create' => '#059669',
                                    'Edit'   => '#d97706',
                                    'Delete' => '#dc2626',
                                ];
                                $aColor = $actionColors[$action] ?? '#6b7280';
                            @endphp
                            <div class="col-6 col-md-3">
                                <label class="perm-action-chip d-flex align-items-center gap-2 p-2 rounded-2 cursor-pointer"
                                       style="border:1.5px solid #e5e7eb;background:#fff;cursor:pointer;transition:all .15s"
                                       for="perm_{{ $resource }}_{{ $loop->index }}"
                                       data-resource="{{ $resource }}">
                                    <input class="form-check-input perm-cb pmod-{{ $module }} m-0"
                                           type="checkbox" name="permissions[]"
                                           value="{{ $key }}"
                                           id="perm_{{ $resource }}_{{ $loop->index }}"
                                           {{ in_array($key, $selected) ? 'checked' : '' }}
                                           onchange="updateBadge('{{ $module }}'); updateChip(this)"
                                           style="flex-shrink:0">
                                    <span class="small fw-medium" style="color:{{ $aColor }};font-size:.8rem">{{ $action }}</span>
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endforeach
</div>

@push('scripts')
<script>
// ── Collapsible permission modules ─────────────────────────────
function togglePermModule(module) {
    var body  = document.getElementById('pbody-' + module);
    var chev  = document.getElementById('chev-' + module);
    var isOpen = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : 'block';
    chev.style.transform = isOpen ? '' : 'rotate(180deg)';
}

// Toggle all checkboxes in a module
function toggleModulePerms(module, checked) {
    document.querySelectorAll('.pmod-' + module).forEach(function(cb) {
        cb.checked = checked;
        updateChip(cb);
    });
    updateBadge(module);
}

// Toggle all checkboxes for a specific resource
function toggleResourcePerms(resource, checked) {
    document.querySelectorAll('[data-resource="' + resource + '"] input[type=checkbox]').forEach(function(cb) {
        cb.checked = checked;
        updateChip(cb);
    });
    // find the module for this resource (first part of resource key)
    var module = resource.split('.')[0];
    updateBadge(module);
}

// Update chip visual state
function updateChip(cb) {
    var label = cb.closest('label');
    if (!label) return;
    if (cb.checked) {
        label.style.borderColor = '#5b4fcf';
        label.style.background  = '#ede9fe';
    } else {
        label.style.borderColor = '#e5e7eb';
        label.style.background  = '#fff';
    }
}

// Update the count badge
function updateBadge(module) {
    var total   = document.querySelectorAll('.pmod-' + module).length;
    var checked = document.querySelectorAll('.pmod-' + module + ':checked').length;
    var badge   = document.getElementById('badge-' + module);
    if (badge) badge.textContent = checked + ' / ' + total;
}

// Init on load
document.addEventListener('DOMContentLoaded', function () {
    // Apply initial chip styles and badge counts
    document.querySelectorAll('.perm-cb').forEach(updateChip);

    @foreach($groups as $module => $resources)
    updateBadge('{{ $module }}');
    @endforeach

    // Auto-open any module that has checked permissions
    @foreach($groups as $module => $resources)
    if (document.querySelectorAll('.pmod-{{ $module }}:checked').length > 0) {
        togglePermModule('{{ $module }}');
    }
    @endforeach
});
</script>
@endpush
