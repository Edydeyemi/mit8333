<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Show registration form.
     */
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    /**
     * Handle public campus registration.
     */
    public function register(RegisterRequest $request): RedirectResponse|JsonResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => StrToLower(trim($request->email)),
                'mobile' => $request->mobile,
                'password' => Hash::make($request->password),
                'role' => 'user',
                'status' => 'active',
            ]);

            $userRole = \App\Models\Admin\Role::where('role_slug', 'user')->first();
            if ($userRole) {
                $user->roles()->attach($userRole->id, ['created_by' => 'system']);
            }

            AuditLog::log('user_registered', $user, [
                'email' => $user->email,
                'role' => $user->role,
            ], $user);

            return $user;
        });

        $msg = 'Your campus account has been registered successfully. Please sign in to continue.';

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $msg,
                'redirect' => route('login'),
            ]);
        }

        return redirect()->route('login')->with('success', $msg);
    }
}

