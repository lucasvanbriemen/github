<?php

namespace App\Listeners;

use App\Models\Milestone;
use App\Models\Item;
use App\Events\MilestoneWebhookReceived;
use App\Services\ImportanceScoreService;
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

        // Recalculate scores for all items in this milestone (due date affects priority)
        $itemsInMilestone = Item::where('milestone_id', $milestoneData->id)->get();
        foreach ($itemsInMilestone as $item) {
            ImportanceScoreService::updateItemScore($item);
        }

        return true;
    }
}
