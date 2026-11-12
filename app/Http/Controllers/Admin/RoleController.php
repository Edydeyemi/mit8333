<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * Display a listing of system roles with permissions matrix and server-side filtering.
     */
    public function index(Request $request): View
    {
        $query = Role::query()->withCount(['permissions', 'users'])->with('permissions');

        if ($search = trim((string) $request->input('q', ''))) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('role_slug', 'like', "%{$search}%");
            });
        }

        $filters = array_filter([
            'q' => $search ?: null,
        ]);

        $roles = $query->orderBy('id', 'asc')
            ->paginate(15)
            ->appends($filters);

        // Load all available permissions grouped by functional module
        $allPermissions = Permission::orderBy('name', 'asc')->get();
        $groupedPermissions = $allPermissions->groupBy(function (Permission $permission): string {
            $parts = explode('.', $permission->name);
            return count($parts) > 1 ? ucfirst($parts[0]) : 'General';
        });

        $totalRoles = Role::count();
        $totalPermissions = $allPermissions->count();

        return view('admin.roles.index', compact(
            'roles',
            'groupedPermissions',
            'allPermissions',
            'totalRoles',
            'totalPermissions',
            'search'
        ));
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'role_slug' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('roles', 'role_slug')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $roleSlug = !empty($validated['role_slug'])
            ? Str::slug($validated['role_slug'], '_')
            : Str::slug($validated['name'], '_');

        // Check if slug is unique
        if (Role::where('role_slug', $roleSlug)->exists()) {
            return back()->withInput()->with('error', "Role slug '{$roleSlug}' already exists. Please choose another.");
        }

        $currentUser = Auth::user();
        if ($roleSlug === 'super_admin' && $currentUser?->role !== 'super_admin') {
            return back()->withInput()->with('error', 'Only Super Admins can create the Super Admin role.');
        }

        $actorName = getActorName($request);

        $role = Role::create([
            'name' => $validated['name'],
            'role_slug' => $roleSlug,
            'created_by' => $actorName,
        ]);

        if (!empty($validated['permissions'])) {
            $permissionIds = Permission::whereIn('name', $validated['permissions'])->pluck('id')->toArray();
            $role->permissions()->syncWithPivotValues($permissionIds, [
                'created_by' => $actorName,
            ]);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', "Role '{$role->name}' created successfully with " . count($validated['permissions'] ?? []) . ' assigned permissions.');
    }

    /**
     * Update the specified role and its permission assignations.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'role_slug' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('roles', 'role_slug')->ignore($role->id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $currentUser = Auth::user();

        if ($role->role_slug === 'super_admin' && $currentUser?->role !== 'super_admin') {
            return redirect()->route('admin.roles.index')->with('error', 'Only Super Admins can modify the Super Admin role.');
        }

        $actorName = getActorName($request);

        $role->update([
            'name' => $validated['name'],
            'role_slug' => Str::slug($validated['role_slug'], '_'),
            'updated_by' => $actorName,
        ]);

        $permissionIds = [];
        if (!empty($validated['permissions'])) {
            $permissionIds = Permission::whereIn('name', $validated['permissions'])->pluck('id')->toArray();
        }

        $role->permissions()->syncWithPivotValues($permissionIds, [
            'updated_by' => $actorName,
        ]);

        return redirect()->route('admin.roles.index')
            ->with('success', "Role '{$role->name}' and its permissions were successfully updated.");
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->role_slug, ['super_admin', 'admin', 'officer', 'user', 'data_entry'], true)) {
            return back()->with('error', "System core role '{$role->name}' is protected and cannot be deleted.");
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', "Cannot delete role '{$role->name}' because it currently has active users assigned.");
        }

        $roleName = $role->name;
        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', "Role '{$roleName}' was successfully deleted.");
    }
}
