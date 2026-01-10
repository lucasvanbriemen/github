<?php

namespace App\Listeners;

use App\Models\Milestone;
use App\Events\MilestoneWebhookReceived;

class ProcessMilestoneWebhook
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public function handle(MilestoneWebhookReceived $event): bool
    {
        $payload = $event->payload;

        $milestoneData = $payload->milestone;
        $repositoryData = $payload->repository;

        Milestone::updateOrCreate(
            ['id' => $milestoneData->id],
            [
                'repository_id' => $repositoryData->id,
                'state' => $milestoneData->state,
                'title' => $milestoneData->title,
                'due_on' => $milestoneData->due_on,
            ]
        );

        return true;
    }
}
