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

    public array $groups;
    public array $orphaned;

    public function __construct(array $groups, array $orphaned = [])
    {
        $this->groups = $groups;
        $this->orphaned = $orphaned;
    }

    public function envelope(): Envelope
    {
        $count = 0;
        foreach ($this->groups as $group) {
            $count += count($group['notifications']);
            foreach ($group['linked'] as $linked) {
                $count += count($linked['notifications']);
            }
        }
        $count += count($this->orphaned);

        return new Envelope(
            subject: "github: {$count} " . ($count === 1 ? 'notification' : 'notifications') . " today",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification_digest',
            with: [
                'groups' => $this->groups,
                'orphaned' => $this->orphaned,
                'baseUrl' => url('/'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
