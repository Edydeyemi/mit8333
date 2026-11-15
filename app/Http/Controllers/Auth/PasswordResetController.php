<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserOtp;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    /**
     * Display password recovery view.
     */
    public function showForgotForm(Request $request): View
    {
        $devOtp = null;
        if ($this->otpService->shouldDisplayDevOtp()) {
            $devOtp = session('dev_otp');
            if (!$devOtp) {
                $email = session('reset_email') ?? old('email') ?? $request->query('email');
                if ($email) {
                    $devOtp = $this->otpService->getLatestActiveOtpForIdentifier((string) $email, UserOtp::TYPE_PASSWORD_RESET);
                }
            }
        }

        return view('auth.password_reset', [
            'devOtp' => $devOtp,
        ]);
    }

    /**
     * Dispatch password recovery OTP to email.
     */
    public function sendResetOtp(ForgotPasswordRequest $request): RedirectResponse|JsonResponse
    {
        $throttleKey = 'reset-otp:' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $msg = "Too many recovery attempts. Please wait {$seconds} seconds.";

            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg], 429);
            }

            return back()->withInput()->withErrors(['email' => $msg]);
        }

        RateLimiter::hit($throttleKey, 60);

        $user = User::where('email', $request->email)->first();
        $code = null;

        if ($user) {
            $code = $this->otpService->generateOtp($user, UserOtp::TYPE_PASSWORD_RESET, 15);
            AuditLog::log('password_reset_otp_dispatched', $user);
        }

        $msg = 'If your university email is registered, a 6-digit recovery code has been sent.';

        if ($request->expectsJson()) {
            $response = [
                'status' => 'success',
                'message' => $msg,
                'email' => $request->email,
            ];
            if ($this->otpService->shouldDisplayDevOtp() && $code !== null) {
                $response['dev_otp'] = $code;
            }
            return response()->json($response);
        }

        $redirect = back()->withInput()->with('status', $msg)->with('reset_email', $request->email);
        if ($this->otpService->shouldDisplayDevOtp() && $code !== null) {
            $redirect->with('dev_otp', $code);
        }

        return $redirect;
    }

    /**
     * Verify OTP and apply new password.
     */
    public function resetPassword(ResetPasswordRequest $request): RedirectResponse|JsonResponse
    {
        $throttleKey = 'reset-attempt:' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $msg = "Too many verification attempts. Please wait {$seconds} seconds.";

            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg], 429);
            }

            return back()->withInput()->withErrors(['code' => $msg]);
        }

        /** @var User|null $user */
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            RateLimiter::hit($throttleKey, 60);
            $msg = 'No campus profile found for the provided email address.';

            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg], 422);
            }

            return back()->withInput()->withErrors(['email' => $msg]);
        }

        $result = $this->otpService->verifyOtp($user, $request->code, UserOtp::TYPE_PASSWORD_RESET);

        if (!$result['success']) {
            RateLimiter::hit($throttleKey, 60);

            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $result['message']], 422);
            }

            return back()->withInput($request->only('email'))->withErrors(['code' => $result['message']]);
        }

        RateLimiter::clear($throttleKey);

        // Update password
        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        AuditLog::log('password_reset_success', $user);

        $msg = 'Your password has been changed successfully. You may now sign in with your new password.';

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

