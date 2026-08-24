<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public const PERMISSION_LIST = [
        'catalog.view'       => 'View Catalog',
        'catalog.manage'     => 'Manage Catalog',
        'inventory.view'     => 'View Inventory',
        'inventory.manage'   => 'Manage Inventory',
        'sales.view'         => 'View Sales',
        'sales.create'       => 'Create Sales',
        'sales.manage'       => 'Manage Sales',
        'purchases.view'     => 'View Purchases',
        'purchases.create'   => 'Create Purchases',
        'purchases.manage'   => 'Manage Purchases',
        'reports.view'       => 'View Reports',
        'settings.view'      => 'View Settings',
        'settings.manage'    => 'Manage Settings',
        'all'                => 'Full Access (Admin)',
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
