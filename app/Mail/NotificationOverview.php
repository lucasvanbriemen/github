<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationOverview extends Mailable
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
            subject: "github: your overview of yesterday{$this->date}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification_overview',
            with: [
                'digestUrl' => url('/') . '/#/notifications/' . $this->date,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
