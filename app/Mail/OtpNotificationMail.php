<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use App\Models\UserOtp;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public string $code,
        public string $type = UserOtp::TYPE_TWO_FACTOR,
        public int $ttlMinutes = 10
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->type) {
            UserOtp::TYPE_PASSWORD_RESET => 'Headworld Campus - Password Recovery Verification Code',
            UserOtp::TYPE_PASSWORD_MODIFICATION => 'Headworld Campus - Security Password Modification Code',
            default => 'Headworld Campus - Your Two-Factor Authentication (2FA) Code',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $heading = match ($this->type) {
            UserOtp::TYPE_PASSWORD_RESET => 'Password Recovery Request',
            UserOtp::TYPE_PASSWORD_MODIFICATION => 'Password Modification Verification',
            default => 'Two-Factor Authentication (2FA)',
        };

        $introMessage = match ($this->type) {
            UserOtp::TYPE_PASSWORD_RESET => 'We received an account recovery request for your Headworld university profile. Enter the 6-digit code below to set a new password.',
            UserOtp::TYPE_PASSWORD_MODIFICATION => 'You requested to modify your account password. Enter this verification code to confirm your identity.',
            default => 'You are signing in to the Headworld Campus Operations Portal. Please provide the 6-digit verification code below to complete your login.',
        };

        return new Content(
            view: 'emails.otp',
            with: [
                'user' => $this->user,
                'code' => $this->code,
                'ttlMinutes' => $this->ttlMinutes,
                'heading' => $heading,
                'introMessage' => $introMessage,
            ],
        );
    }
}

