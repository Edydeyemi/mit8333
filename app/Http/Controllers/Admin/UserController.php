<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBackendUserRequest;
use App\Http\Requests\Admin\UpdateBackendUserRequest;
use App\Models\Admin\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of personnel and campus users with server-side filtering.
     */
    public function index(Request $request): View
    {
        $query = User::query()->with('roles');

        // Search filter
        if ($search = trim((string) $request->input('q', ''))) {
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Role filter
        $role = $request->input('role', 'all');
        if ($role !== 'all' && !empty($role)) {
            $query->where('role', $role);
        }

        // Status filter
        $status = $request->input('status', 'all');
        if ($status !== 'all' && !empty($status)) {
            $query->where('status', $status);
        }

        $totalUsers = User::count();
        $totalOfficers = User::where('role', 'officer')->count();
        $totalAdmins = User::whereIn('role', ['admin', 'super_admin'])->count();
        $totalActive = User::where('status', 'active')->count();

        $users = $query->latest('id')
            ->paginate(15)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        return view('admin.users.index', compact(
            'users',
            'roles',
            'totalUsers',
            'totalOfficers',
            'totalAdmins',
            'totalActive',
            'search',
            'role',
            'status'
        ));
    }

    /**
     * Store a newly created backend user in storage.
     */
    public function store(StoreBackendUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $currentUser = Auth::user();

        // Enforce Super Admin hierarchy
        if ($validated['role'] === 'super_admin' && $currentUser?->role !== 'super_admin') {
            return back()->withInput()->with('error', 'Only existing Super Administrators can provision another Super Administrator.');
        }

        // Generate clean unique slug
        $base = Str::slug("{$validated['first_name']}-{$validated['last_name']}");
        $slug = $base . '-' . Str::lower(Str::random(6));

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'mobile' => $validated['mobile'] ?? null,
            'role' => $validated['role'],
            'status' => $validated['status'],
            'password' => Hash::make($validated['password']),
            'slug' => $slug,
            'email_verified_at' => now(),
        ]);

        // Sync to role_user pivot table
        $matchingRole = Role::where('role_slug', $validated['role'])->first();
        if ($matchingRole) {
            $user->roles()->syncWithoutDetaching([
                $matchingRole->id => ['created_by' => getActorName($request)],
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', "Personnel account for {$user->name} ({$user->role}) was provisioned successfully.");
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateBackendUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $currentUser = Auth::user();

        // Prevent self-demotion or removal of super_admin
        if ($currentUser && $currentUser->id === $user->id && $validated['role'] !== $user->role) {
            return back()->withInput()->with('error', 'You cannot alter your own administrative role.');
        }

        if ($user->role === 'super_admin' && $validated['role'] !== 'super_admin') {
            $otherSuperAdminCount = User::where('role', 'super_admin')->where('id', '!=', $user->id)->count();
            if ($otherSuperAdminCount === 0) {
                return back()->withInput()->with('error', 'Cannot demote the sole system Super Administrator.');
            }
        }

        $payload = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'mobile' => $validated['mobile'] ?? null,
            'role' => $validated['role'],
            'status' => $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        // Update role pivot
        $matchingRole = Role::where('role_slug', $validated['role'])->first();
        if ($matchingRole) {
            $user->roles()->sync([
                $matchingRole->id => ['created_by' => getActorName($request)],
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', "Personnel profile for {$user->name} was successfully updated.");
    }

    /**
     * Toggle active/banned status for the specified user.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        $currentUser = Auth::user();

        if ($currentUser && $currentUser->id === $user->id) {
            return back()->with('error', 'You cannot suspend your own authenticated session.');
        }

        if ($user->role === 'super_admin') {
            return back()->with('error', 'Super Administrator accounts cannot be suspended.');
        }

        $user->status = ($user->status === 'active') ? 'banned' : 'active';
        $user->save();

        $action = ($user->status === 'active') ? 'reactivated' : 'suspended';

        return redirect()->back()
            ->with('success', "Account for {$user->name} has been {$action}.");
    }
}

