<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Granular permission keys.
     *
     * Rules enforced everywhere:
     *  - If a section has NO sub-permission granted → the whole header is hidden
     *  - view   = read-only access (index / show)
     *  - create = can add new records
     *  - edit   = can modify existing records
     *  - delete = can delete records
     *  - manage = shorthand for view+create+edit+delete on that resource
     *  - 'all'  = Full Admin (bypasses everything)
     */
    public const PERMISSION_LIST = [
        // ── CATALOG ──────────────────────────────────────
        'catalog.vehicle-types.view'        => 'Catalog — Vehicle Types: View',
        'catalog.vehicle-types.create'      => 'Catalog — Vehicle Types: Create',
        'catalog.vehicle-types.edit'        => 'Catalog — Vehicle Types: Edit',
        'catalog.vehicle-types.delete'      => 'Catalog — Vehicle Types: Delete',

        'catalog.vehicle-models.view'       => 'Catalog — Vehicle Models: View',
        'catalog.vehicle-models.create'     => 'Catalog — Vehicle Models: Create',
        'catalog.vehicle-models.edit'       => 'Catalog — Vehicle Models: Edit',
        'catalog.vehicle-models.delete'     => 'Catalog — Vehicle Models: Delete',

        'catalog.part-categories.view'      => 'Catalog — Part Categories: View',
        'catalog.part-categories.create'    => 'Catalog — Part Categories: Create',
        'catalog.part-categories.edit'      => 'Catalog — Part Categories: Edit',
        'catalog.part-categories.delete'    => 'Catalog — Part Categories: Delete',

        'catalog.spare-parts.view'          => 'Catalog — Spare Parts: View',
        'catalog.spare-parts.create'        => 'Catalog — Spare Parts: Create',
        'catalog.spare-parts.edit'          => 'Catalog — Spare Parts: Edit',
        'catalog.spare-parts.delete'        => 'Catalog — Spare Parts: Delete',

        'catalog.units.view'                => 'Catalog — Units: View',
        'catalog.units.create'              => 'Catalog — Units: Create',
        'catalog.units.edit'                => 'Catalog — Units: Edit',
        'catalog.units.delete'              => 'Catalog — Units: Delete',

        // ── INVENTORY ─────────────────────────────────────
        'inventory.current-stock.view'      => 'Inventory — Current Stock: View',

        'inventory.stock-in.view'           => 'Inventory — Stock Entry: View',
        'inventory.stock-in.create'         => 'Inventory — Stock Entry: Create',

        'inventory.adjustments.view'        => 'Inventory — Adjustments: View',
        'inventory.adjustments.create'      => 'Inventory — Adjustments: Create',

        'inventory.transfers.view'          => 'Inventory — Stock Transfer: View',
        'inventory.transfers.create'        => 'Inventory — Stock Transfer: Create',

        'inventory.history.view'            => 'Inventory — History: View',

        // ── SALES ─────────────────────────────────────────
        'sales.view'                        => 'Sales — Sales History: View',
        'sales.create'                      => 'Sales — New Sale: Create',
        'sales.delete'                      => 'Sales — Sale: Delete',

        'sales.returns.view'                => 'Sales — Returns: View',
        'sales.returns.create'              => 'Sales — Returns: Create',

        'sales.customers.view'              => 'Sales — Customers: View',
        'sales.customers.create'            => 'Sales — Customers: Create',
        'sales.customers.edit'              => 'Sales — Customers: Edit',
        'sales.customers.delete'            => 'Sales — Customers: Delete',

        // ── PURCHASES ─────────────────────────────────────
        'purchases.view'                    => 'Purchases — Purchase History: View',
        'purchases.create'                  => 'Purchases — New Purchase: Create',
        'purchases.delete'                  => 'Purchases — Purchase: Delete',

        'purchases.suppliers.view'          => 'Purchases — Suppliers: View',
        'purchases.suppliers.create'        => 'Purchases — Suppliers: Create',
        'purchases.suppliers.edit'          => 'Purchases — Suppliers: Edit',
        'purchases.suppliers.delete'        => 'Purchases — Suppliers: Delete',

        // ── EXPENSES ──────────────────────────────────────────────────────
        'expenses.view'                     => 'Expenses — History: View',
        'expenses.create'                   => 'Expenses — New Expense: Create',
        'expenses.edit'                     => 'Expenses — Expense: Edit',
        'expenses.delete'                   => 'Expenses — Expense: Delete',
        'reports.expenses'                  => 'Reports — Expenses Report',

        // ── REPORTS ───────────────────────────────────────
        'reports.sales'                     => 'Reports — Sales Report',
        'reports.vehicles'                  => 'Reports — Vehicles Report',
        'reports.spare-parts'               => 'Reports — Spare Parts Report',
        'reports.stock'                     => 'Reports — Stock Report',
        'reports.low-stock'                 => 'Reports — Low Stock Report',
        'reports.purchases'                 => 'Reports — Purchases Report',
        'reports.profit'                    => 'Reports — Profit Report',

        // ── SETTINGS ──────────────────────────────────────
        'settings.company'                  => 'Settings — Company Profile',
        'settings.users.view'               => 'Settings — Users: View',
        'settings.users.create'             => 'Settings — Users: Create',
        'settings.users.edit'               => 'Settings — Users: Edit',
        'settings.users.delete'             => 'Settings — Users: Delete',
        'settings.roles.view'               => 'Settings — Roles: View',
        'settings.roles.create'             => 'Settings — Roles: Create',
        'settings.roles.edit'               => 'Settings — Roles: Edit',
        'settings.roles.delete'             => 'Settings — Roles: Delete',
        'settings.warehouses.view'          => 'Settings — Warehouses: View',
        'settings.warehouses.create'        => 'Settings — Warehouses: Create',
        'settings.warehouses.edit'          => 'Settings — Warehouses: Edit',
        'settings.warehouses.delete'        => 'Settings — Warehouses: Delete',

        // ── ADMIN ─────────────────────────────────────────
        'warehouse.full_access'             => 'Full Warehouse Access (Manager — sees all users in assigned warehouses)',
        'all'                               => 'Full Access (Super Admin — all warehouses, no restrictions)',
    ];

    public function index()
    {
        $roles = Role::withCount('users')->latest()->get();
        return view('settings.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = self::PERMISSION_LIST;
        return view('settings.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:50|unique:roles,name',
            'display_name' => 'required|string|max:100',
            'description'  => 'nullable|string|max:300',
            'permissions'  => 'nullable|array',
            'permissions.*'=> 'string',
        ]);

        $data['permissions'] = $data['permissions'] ?? [];
        Role::create($data);

        return redirect()->route('settings.roles.index')
            ->with('success', "Role '{$data['display_name']}' created.");
    }

    public function edit(Role $role)
    {
        $permissions = self::PERMISSION_LIST;
        return view('settings.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'display_name' => 'required|string|max:100',
            'description'  => 'nullable|string|max:300',
            'permissions'  => 'nullable|array',
            'permissions.*'=> 'string',
        ]);

        $data['permissions'] = $data['permissions'] ?? [];
        $role->update($data);

        return redirect()->route('settings.roles.index')
            ->with('success', 'Role updated.');
    }

    public function destroy(Role $role)
    {
        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete: role has users. Reassign them first.');
        }
        $role->delete();
        return redirect()->route('settings.roles.index')
            ->with('success', 'Role deleted.');
    }
}
