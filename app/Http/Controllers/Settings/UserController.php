<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('role', 'warehouses');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }
        if ($request->role) {
            $query->where('role_id', $request->role);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->paginate(20)->withQueryString();
        $roles = Role::orderBy('display_name')->get();

        return view('settings.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles      = Role::orderBy('display_name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();
        return view('settings.users.create', compact('roles', 'warehouses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'email'         => 'required|email|unique:users,email',
            'phone'         => 'nullable|string|max:20',
            'role_id'       => 'nullable|exists:roles,id',
            'password'      => 'required|string|min:8|confirmed',
            'status'        => 'required|in:active,inactive',
            'access_level'  => 'required|in:regular,manager,super_admin',
            'warehouse_ids' => 'nullable|array',
            'warehouse_ids.*' => 'exists:warehouses,id',
        ]);

        $data['password'] = Hash::make($data['password']);
        $warehouseIds = $request->input('warehouse_ids', []);
        unset($data['warehouse_ids']);

        $user = User::create($data);
        $user->warehouses()->sync($warehouseIds);

        return redirect()->route('settings.users.index')
            ->with('success', "User '{$user->name}' created successfully.");
    }

    public function edit(User $user)
    {
        $roles      = Role::orderBy('display_name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();
        return view('settings.users.edit', compact('user', 'roles', 'warehouses'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'phone'         => 'nullable|string|max:20',
            'role_id'       => 'nullable|exists:roles,id',
            'status'        => 'required|in:active,inactive',
            'access_level'  => 'required|in:regular,manager,super_admin',
            'warehouse_ids' => 'nullable|array',
            'warehouse_ids.*' => 'exists:warehouses,id',
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8|confirmed']);
            $data['password'] = Hash::make($request->password);
        }

        $warehouseIds = $request->input('warehouse_ids', []);
        unset($data['warehouse_ids']);

        $user->update($data);
        $user->warehouses()->sync($warehouseIds);

        return redirect()->route('settings.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();
        return redirect()->route('settings.users.index')
            ->with('success', 'User deleted.');
    }
}
