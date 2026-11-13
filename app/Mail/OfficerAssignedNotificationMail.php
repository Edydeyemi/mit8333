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

class OfficerAssignedNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ServiceRequest $serviceRequest,
        public User $recipient,
        public User $officer,
        public bool $isOfficerRecipient = false
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isOfficerRecipient
            ? "[TASK ALLOCATION] New Work Order Assigned: {$this->serviceRequest->tracking_code}"
            : "Headworld Facility - Technician Dispatched to Request: {$this->serviceRequest->tracking_code}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.officer_assigned',
        );
    }
}

