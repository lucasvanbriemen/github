<?php

namespace App\Mail;

use App\Models\PullRequestReview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PullRequestReviewed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public PullRequestReview $pullRequestReview;
    public function __construct(PullRequestReview $pullRequestReview)
    {
        $this->pullRequestReview = $pullRequestReview;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pull Request Reviewed',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.pull_request_reviewed',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
