<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestStatusUpdatedNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ServiceRequest $serviceRequest,
        public User $recipient,
        public string $previousStatus,
        public string $newStatus,
        public ?string $notes = null
    ) {}

    public function envelope(): Envelope
    {
        $statusLabel = strtoupper(str_replace('_', ' ', $this->newStatus));
        return new Envelope(
            subject: "Headworld Facility - Status Update [{$statusLabel}]: {$this->serviceRequest->tracking_code}"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.request_status_updated',
        );
    }
}

