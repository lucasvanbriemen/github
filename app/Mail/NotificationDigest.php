<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationDigest extends Mailable
{
    use Queueable, SerializesModels;

    public string $date;

    public function __construct(string $date)
    {
        $this->date = $date;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "github: notification digest {$this->date}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification_digest',
            with: [
                'digestUrl' => url('/') . '/#/notifications/digest/' . $this->date,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
