<?php

namespace App\Mail;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationCreated extends Mailable
{
    use Queueable, SerializesModels;

    public Notification $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "github: {$this->notification->subject}",
        );
    }

    public function content(): Content
    {
        $notificationUrl = url('/') . '/#/notification/' . $this->notification->id;

        return new Content(
            view: 'emails.notification_created',
            with: [
                'notificationUrl' => $notificationUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
