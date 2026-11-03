<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HrmsNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectText,
        public string $bodyText,
        public string $companyName,
        public string $employeeName,
        public ?string $actionUrl = null,
        public ?string $actionText = 'View in HRMS Portal',
        public ?string $category = 'HRMS Notification',
        public ?string $companyCode = 'VE'
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[{$this->companyName}] {$this->subjectText}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.hrms_notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

