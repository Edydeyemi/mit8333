<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::withCount(['permissions', 'users'])->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $permissions = Permission::all();

        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            // 'role_slug' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('roles', 'role_slug')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $currentUser = Auth::user();
        if ($validated['role_slug'] === 'super_admin' && !$currentUser->isSuperAdmin()) {
            return back()->withInput()->with('error', 'Only Super Admins can create the Super Admin role.');
        }

        $role = Role::create([
            'name' => $validated['name'],
            'role_slug' => $validated['role_slug'],
            'created_by' => getActorName($request) ?? 'admin',
        ]);

        if (!empty($validated['permissions'])) {
            $permissionIds = Permission::whereIn('name', $validated['permissions'])->pluck('id')->toArray();
            $role->permissions()->syncWithPivotValues($permissionIds, [
                'created_by' => getActorName($request) ?? 'admin',
            ]);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): View|RedirectResponse
    {
        $currentUser = Auth::user();

        if ($role->role_slug === 'super_admin' && !$currentUser->isSuperAdmin()) {
            return redirect()->route('admin.roles.index')->with('error', 'Only Super Admins can modify the Super Admin role.');
        }

        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'role_slug' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('roles', 'role_slug')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);


        $currentUser = Auth::user();

        if ($role->role_slug === 'super_admin' && !$currentUser->isSuperAdmin()) {
            return redirect()->route('admin.roles.index')->with('error', 'Only Super Admins can modify the Super Admin role.');
        }

        $validated = $request->validated();

        $role->update([
            'name' => $validated['name'],
            'role_slug' => $validated['role_slug'],
            'updated_by' => $currentUser->username ?? 'admin',
        ]);

        $permissionIds = [];
        if (!empty($validated['permissions'])) {
            $permissionIds = Permission::whereIn('name', $validated['permissions'])->pluck('id')->toArray();
        }

        $role->permissions()->syncWithPivotValues($permissionIds, [
            'updated_by' => $currentUser->username ?? 'admin',
        ]);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->role_slug, ['super_admin', 'admin', 'user'])) {
            return back()->with('error', 'System default roles cannot be deleted.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', 'Cannot delete role assigned to active users.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }
}
