<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\TwoFactorVerifyRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserOtp;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {
    }

    /**
     * Show login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->to($this->resolveDashboardPath(Auth::user()));
        }

        return view('auth.login');
    }

    /**
     * Handle initial credentials verification and trigger 2FA OTP.
     */
    public function login(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $throttleKey = 'login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $msg = "Too many login attempts. Please wait {$seconds} seconds before trying again.";

            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg], 429);
            }

            return back()->withInput($request->only('email'))->withErrors(['email' => $msg]);
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::validate($credentials)) {
            RateLimiter::hit($throttleKey, 60);

            AuditLog::log('login_failed_credentials', null, [
                'email' => $request->input('email'),
                'ip' => $request->ip(),
            ]);

            $msg = 'Invalid email or password credentials.';

            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg], 422);
            }

            return back()->withInput($request->only('email'))->withErrors(['email' => $msg]);
        }

        /** @var User $user */
        $user = User::where('email', $request->email)->firstOrFail();

        if ($user->isBanned()) {
            $msg = 'Your campus profile is suspended. Please contact the Facility Operations Desk.';

            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg], 403);
            }

            return back()->withInput($request->only('email'))->withErrors(['email' => $msg]);
        }

        RateLimiter::clear($throttleKey);

        // Generate and dispatch 2FA OTP
        $code = $this->otpService->generateOtp($user, UserOtp::TYPE_TWO_FACTOR);

        // Store pending user in session
        $request->session()->put('auth.2fa.user_id', $user->id);
        $request->session()->put('auth.2fa.remember', (bool) $request->boolean('remember'));

        AuditLog::log('login_2fa_dispatched', $user);

        if ($request->expectsJson()) {
            $response = [
                'status' => 'success',
                'requires_2fa' => true,
                'message' => 'Verification code sent to your registered email address.',
                'redirect' => route('login.2fa'),
            ];
            if ($this->otpService->shouldDisplayDevOtp()) {
                $response['dev_otp'] = $code;
            }
            return response()->json($response);
        }

        $redirect = redirect()->route('login.2fa')->with(
            'status',
            'A 6-digit verification code has been dispatched to your email address.'
        );

        if ($this->otpService->shouldDisplayDevOtp()) {
            $redirect->with('dev_otp', $code);
        }

        return $redirect;
    }

    /**
     * Show 2FA verification form.
     */
    public function showTwoFactorForm(Request $request): View|RedirectResponse
    {
        $userId = $request->session()->get('auth.2fa.user_id');

        if (!$userId) {
            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please sign in again.']);
        }

        $user = User::find($userId);

        if (!$user) {
            $request->session()->forget(['auth.2fa.user_id', 'auth.2fa.remember']);
            return redirect()->route('login');
        }

        // Mask email for display (e.g. s***t@unilag.edu)
        $maskedEmail = $this->maskEmail($user->email);
        $cooldown = $this->otpService->getRemainingCooldown($user, UserOtp::TYPE_TWO_FACTOR);

        $devOtp = null;
        if ($this->otpService->shouldDisplayDevOtp()) {
            $devOtp = session('dev_otp') ?? $this->otpService->getLatestActiveOtp($user, UserOtp::TYPE_TWO_FACTOR);
        }

        return view('auth.two_factor', [
            'user' => $user,
            'maskedEmail' => $maskedEmail,
            'cooldown' => $cooldown,
            'devOtp' => $devOtp,
        ]);
    }

    /**
     * Verify 2FA OTP and authenticate session.
     */
    public function verifyTwoFactor(TwoFactorVerifyRequest $request): RedirectResponse|JsonResponse
    {
        $userId = $request->session()->get('auth.2fa.user_id');

        if (!$userId) {
            $msg = 'Your authentication session has expired. Please log in again.';
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg], 419);
            }
            return redirect()->route('login')->withErrors(['email' => $msg]);
        }

        /** @var User $user */
        $user = User::findOrFail($userId);

        $result = $this->otpService->verifyOtp($user, $request->code, UserOtp::TYPE_TWO_FACTOR);

        if (!$result['success']) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $result['message']], 422);
            }
            return back()->withErrors(['code' => $result['message']]);
        }

        // Authenticate user
        $remember = (bool) $request->session()->get('auth.2fa.remember', false);
        $request->session()->forget(['auth.2fa.user_id', 'auth.2fa.remember']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $user->last_seen = now();
        $user->save();

        AuditLog::log('login_2fa_success', $user);

        $targetUrl = $this->resolveDashboardPath($user);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Authenticated successfully.',
                'redirect' => $targetUrl,
            ]);
        }

        return redirect()->intended($targetUrl)->with('success', "Welcome back, {$user->name}!");
    }

    /**
     * Resend 2FA OTP.
     */
    public function resendTwoFactor(Request $request): RedirectResponse|JsonResponse
    {
        $userId = $request->session()->get('auth.2fa.user_id');

        if (!$userId) {
            $msg = 'Session expired. Please sign in again.';
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg], 419);
            }
            return redirect()->route('login')->withErrors(['email' => $msg]);
        }

        /** @var User $user */
        $user = User::findOrFail($userId);

        if ($this->otpService->isOnResendCooldown($user, UserOtp::TYPE_TWO_FACTOR)) {
            $remaining = $this->otpService->getRemainingCooldown($user, UserOtp::TYPE_TWO_FACTOR);
            $msg = "Please wait {$remaining} seconds before requesting a new code.";

            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg], 429);
            }

            return back()->withErrors(['resend' => $msg]);
        }

        $code = $this->otpService->generateOtp($user, UserOtp::TYPE_TWO_FACTOR);

        $msg = 'A fresh 6-digit verification code has been dispatched to your email.';

        if ($request->expectsJson()) {
            $response = ['status' => 'success', 'message' => $msg];
            if ($this->otpService->shouldDisplayDevOtp()) {
                $response['dev_otp'] = $code;
            }
            return response()->json($response);
        }

        $redirect = back()->with('status', $msg);
        if ($this->otpService->shouldDisplayDevOtp()) {
            $redirect->with('dev_otp', $code);
        }

        return $redirect;
    }

    /**
     * Logout authenticated user.
     */
    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        if ($user) {
            AuditLog::log('logout', $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Signed out successfully.',
                'redirect' => route('login'),
            ]);
        }

        return redirect()->route('login')->with('status', 'You have been safely signed out.');
    }

    /**
     * Resolve target dashboard by user role per AGENTS.md.
     */
    protected function resolveDashboardPath(User $user): string
    {
        return match ($user->role) {
            'super_admin', 'admin', 'data_entry' => url('/admin'),
            'officer' => url('/facility'),
            default => url('/portal'),
        };
    }

    /**
     * Mask an email address for privacy.
     */
    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) < 2) {
            return $email;
        }

        $name = $parts[0];
        $domain = $parts[1];

        $len = strlen($name);
        if ($len <= 2) {
            $masked = $name . '***';
        } else {
            $masked = substr($name, 0, 1) . str_repeat('*', max(3, $len - 2)) . substr($name, -1);
        }

        return $masked . '@' . $domain;
    }
}

