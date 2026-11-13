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

class TaskCompletedNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ServiceRequest $serviceRequest,
        public User $recipient,
        public ?string $resolutionNotes = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Headworld Facility - Work Order Resolved: {$this->serviceRequest->tracking_code}"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.task_completed',
        );
    }
}

