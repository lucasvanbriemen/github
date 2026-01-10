<?php

namespace App\Listeners;

use App\Models\Milestone;
use App\Events\MilestoneWebhookReceived;
use Carbon\Carbon;

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
                'number' => $milestoneData->number,
                'title' => $milestoneData->title,
                'due_on' => Carbon::parse($milestoneData->due_on),
            ]
        );

        return true;
    }
}
