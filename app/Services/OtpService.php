<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\OtpNotificationMail;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserOtp;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public const MAX_ATTEMPTS = 5;
    public const DEFAULT_TTL_MINUTES = 10;

    /**
     * Generate and dispatch a secure 6-digit OTP to user.
     */
    public function generateOtp(
        User $user,
        string $type = UserOtp::TYPE_TWO_FACTOR,
        int $durationMinutes = self::DEFAULT_TTL_MINUTES
    ): string {
        // Void prior active OTPs of same type for this user
        UserOtp::where('user_id', $user->id)
            ->where('type', $type)
            ->delete();

        // Cryptographically secure 6-digit code
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $otp = UserOtp::create([
            'user_id' => $user->id,
            'identifier' => $user->email,
            'code' => $code,
            'type' => $type,
            'attempts' => 0,
            'expires_at' => now()->addMinutes($durationMinutes),
        ]);

        // Send Email notification
        try {
            Mail::to($user->email)->send(new OtpNotificationMail($user, $code, $type, $durationMinutes));
        } catch (Exception $e) {
            Log::error("Failed to send OTP email to {$user->email}: " . $e->getMessage());
        }

        return $code;
    }

    /**
     * Verify a supplied 6-digit OTP.
     *
     * @return array{success: bool, message: string}
     */
    public function verifyOtp(User $user, string $code, string $type = UserOtp::TYPE_TWO_FACTOR): array
    {
        $otp = UserOtp::where('user_id', $user->id)
            ->where('type', $type)
            ->latest('id')
            ->first();

        if (!$otp) {
            return [
                'success' => false,
                'message' => 'No active verification code found. Please request a new code.',
            ];
        }

        if ($otp->isExpired()) {
            $otp->delete();
            return [
                'success' => false,
                'message' => 'The verification code has expired. Please request a new code.',
            ];
        }

        if ($otp->hasExceededMaxAttempts(self::MAX_ATTEMPTS)) {
            $otp->delete();
            AuditLog::log("otp_max_attempts_exceeded_{$type}", $user, ['user_id' => $user->id]);
            return [
                'success' => false,
                'message' => 'Maximum verification attempts exceeded. Please request a new code.',
            ];
        }

        if (!$otp->matches($code)) {
            $otp->incrementAttempts();
            $remaining = max(0, self::MAX_ATTEMPTS - $otp->attempts);

            AuditLog::log("otp_failed_attempt_{$type}", $user, [
                'user_id' => $user->id,
                'attempts' => $otp->attempts,
            ]);

            return [
                'success' => false,
                'message' => "Invalid verification code. {$remaining} attempt(s) remaining.",
            ];
        }

        // Code matches and valid: consume OTP
        $otp->delete();

        AuditLog::log("otp_verified_{$type}", $user, ['user_id' => $user->id]);

        return [
            'success' => true,
            'message' => 'Verification successful.',
        ];
    }

    /**
     * Check if user is on resend cooldown.
     */
    public function isOnResendCooldown(User $user, string $type, int $cooldownSeconds = 60): bool
    {
        $latest = UserOtp::where('user_id', $user->id)
            ->where('type', $type)
            ->latest('id')
            ->first();

        if (!$latest) {
            return false;
        }

        return $latest->created_at->diffInSeconds(now()) < $cooldownSeconds;
    }

    /**
     * Get remaining cooldown seconds.
     */
    public function getRemainingCooldown(User $user, string $type, int $cooldownSeconds = 60): int
    {
        $latest = UserOtp::where('user_id', $user->id)
            ->where('type', $type)
            ->latest('id')
            ->first();

        if (!$latest) {
            return 0;
        }

        $elapsed = $latest->created_at->diffInSeconds(now());
        return (int) max(0, $cooldownSeconds - $elapsed);
    }
}

