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
        // Your overview of March 15, 2024 (-1day of current date)
        return new Envelope(
            subject: "Your overview of " . date('F j, Y', strtotime($this->date . ' -1 day')),
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
