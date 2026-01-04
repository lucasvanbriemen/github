<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\PullRequest;

class PullRequestUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $pullRequest;
    public $updateType;
    public $metadata;

    public function __construct(PullRequest $pullRequest, string $updateType = 'push', array $metadata = [])
    {
        $this->pullRequest = $pullRequest;
        $this->updateType = $updateType;
        $this->metadata = $metadata;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('pr.' . $this->pullRequest->id),
            new PrivateChannel('repository.' . $this->pullRequest->repository_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pr.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'pr_id' => $this->pullRequest->id,
            'pr_number' => $this->pullRequest->number,
            'update_type' => $this->updateType,
            'head_sha' => $this->pullRequest->head_sha,
            'head_branch' => $this->pullRequest->head_branch,
            'metadata' => $this->metadata,
            'timestamp' => now()->toISOString(),
        ];
    }
}
