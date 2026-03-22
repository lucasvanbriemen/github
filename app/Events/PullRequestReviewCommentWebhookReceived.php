<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PullRequestReviewCommentWebhookReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
    }
}
