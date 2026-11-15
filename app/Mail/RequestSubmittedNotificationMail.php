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

class RequestSubmittedNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ServiceRequest $serviceRequest,
        public User $recipient,
        public bool $isDispatcher = false
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isDispatcher
            ? "[URGENT DISPATCH] New Incident Lodged: {$this->serviceRequest->tracking_code}"
            : "Headworld Facility - Service Request Confirmed: {$this->serviceRequest->tracking_code}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.request_submitted',
        );
    }
}

