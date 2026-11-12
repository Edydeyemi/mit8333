<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PermissionController extends Controller
{
    /**
     * Display all system permissions stored in the database.
     */
    public function index(): View
    {
        $permissions = Permission::withCount('roles')->orderBy('name')->get();

        return view('admin.permissions.index', compact('permissions'));
    }

    /**
     * Sync permissions defined in AppPermissions.php into the permissions DB table.
     */
    public function sync(): RedirectResponse
    {
        $appPermissions = file_exists(base_path('AppPermissions.php'))
            ? require base_path('AppPermissions.php')
            : ['permissions' => []];

        $permissionList = $appPermissions['permissions'] ?? [];

        $syncedCount = 0;
        foreach ($permissionList as $permKey => $permDescription) {
            Permission::updateOrCreate(
                ['name' => $permKey],
                ['description' => $permDescription]
            );
            $syncedCount++;
        }

        return redirect()->route('admin.roles.index')
            ->with('success', "Permissions synchronized successfully ({$syncedCount} canonical permissions updated in catalog).");
    }
}
