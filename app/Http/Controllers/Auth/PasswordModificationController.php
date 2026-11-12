<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserOtp;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PasswordModificationController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    /**
     * Dispatch OTP to authenticated user for password modification.
     */
    public function sendModificationOtp(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($this->otpService->isOnResendCooldown($user, UserOtp::TYPE_PASSWORD_MODIFICATION)) {
            $remaining = $this->otpService->getRemainingCooldown($user, UserOtp::TYPE_PASSWORD_MODIFICATION);
            $msg = "Please wait {$remaining} seconds before requesting another code.";

            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg], 429);
            }

            return back()->withErrors(['otp' => $msg]);
        }

        $this->otpService->generateOtp($user, UserOtp::TYPE_PASSWORD_MODIFICATION, 15);
        AuditLog::log('password_modification_otp_dispatched', $user);

        $msg = 'A 6-digit confirmation code has been dispatched to your email address.';

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => $msg]);
        }

        return back()->with('status', $msg);
    }

    /**
     * Update authenticated user's password.
     */
    public function update(UpdatePasswordRequest $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            $msg = 'The current password provided is incorrect.';

            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg], 422);
            }

            return back()->withErrors(['current_password' => $msg]);
        }

        // If OTP code is submitted, verify it
        if ($request->filled('code')) {
            $result = $this->otpService->verifyOtp($user, $request->code, UserOtp::TYPE_PASSWORD_MODIFICATION);

            if (!$result['success']) {
                if ($request->expectsJson()) {
                    return response()->json(['status' => 'error', 'message' => $result['message']], 422);
                }

                return back()->withErrors(['code' => $result['message']]);
            }
        }

        // Apply new password
        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        AuditLog::log('password_modified', $user);

        $msg = 'Your account password has been successfully updated.';

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }
}

