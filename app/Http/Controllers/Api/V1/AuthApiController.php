<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserOtp;
use App\Services\OtpService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected OtpService $otpService
    ) {}

    /**
     * API Login - Step 1: Validate credentials & dispatch 2FA OTP.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $throttleKey = 'api-login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->errorResponse("Too many attempts. Retry in {$seconds} seconds.", 429);
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::validate($credentials)) {
            RateLimiter::hit($throttleKey, 60);
            return $this->errorResponse('Invalid email or password credentials.', 401);
        }

        /** @var User $user */
        $user = User::where('email', $request->email)->firstOrFail();

        if ($user->isBanned()) {
            return $this->errorResponse('Your account has been suspended.', 403);
        }

        RateLimiter::clear($throttleKey);

        // Generate and dispatch 2FA OTP
        $code = $this->otpService->generateOtp($user, UserOtp::TYPE_TWO_FACTOR);

        // Generate temporary challenge token (valid for 10 minutes)
        $challenge = Str::random(40);
        cache()->put("2fa_challenge:{$challenge}", $user->id, now()->addMinutes(10));

        AuditLog::log('api_login_2fa_dispatched', $user);

        $payload = [
            'requires_2fa' => true,
            'challenge_token' => $challenge,
            'email' => $user->email,
        ];

        if ($this->otpService->shouldDisplayDevOtp()) {
            $payload['dev_otp'] = $code;
        }

        return $this->successResponse($payload, '2FA verification code dispatched to email. Complete verification to receive access token.', 200);
    }

    /**
     * API Login - Step 2: Verify 2FA OTP & issue Sanctum Access Token.
     */
    public function verify2fa(Request $request): JsonResponse
    {
        $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = cache()->get("2fa_challenge:{$request->challenge_token}");

        if (!$userId) {
            return $this->errorResponse('Invalid or expired 2FA session challenge. Please sign in again.', 401);
        }

        /** @var User $user */
        $user = User::findOrFail($userId);

        $result = $this->otpService->verifyOtp($user, $request->code, UserOtp::TYPE_TWO_FACTOR);

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 422);
        }

        // Clean up challenge token
        cache()->forget("2fa_challenge:{$request->challenge_token}");

        // Create Sanctum API token
        $token = $user->createToken('api_access_token')->plainTextToken;

        $user->last_seen = now();
        $user->save();

        AuditLog::log('api_login_success', $user);

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'slug' => $user->slug,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Authentication successful.', 200);
    }

    /**
     * API Forgot Password - Dispatch recovery OTP.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();
        $code = null;

        if ($user) {
            $code = $this->otpService->generateOtp($user, UserOtp::TYPE_PASSWORD_RESET, 15);
            AuditLog::log('api_password_reset_otp_dispatched', $user);
        }

        $payload = ['email' => $request->email];
        if ($this->otpService->shouldDisplayDevOtp() && $code !== null) {
            $payload['dev_otp'] = $code;
        }

        return $this->successResponse(
            $payload,
            'If your email is registered, a 6-digit recovery code has been sent.',
            200
        );
    }

    /**
     * API Reset Password - Verify OTP & apply new password.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->errorResponse('No account registered with this email address.', 404);
        }

        $result = $this->otpService->verifyOtp($user, $request->code, UserOtp::TYPE_PASSWORD_RESET);

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 422);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        // Revoke existing API tokens for safety
        $user->tokens()->delete();

        AuditLog::log('api_password_reset_success', $user);

        return $this->successResponse(null, 'Password has been reset successfully. Please sign in with your new credentials.', 200);
    }

    /**
     * API Logout - Revoke Sanctum Token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->currentAccessToken()?->delete();
            AuditLog::log('api_logout', $user);
        }

        return $this->successResponse(null, 'Logged out successfully.', 200);
    }
}

