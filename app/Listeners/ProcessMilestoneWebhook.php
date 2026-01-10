<?php

namespace App\Listeners;

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

    /**
     * Handle the event.
     */
    public function handle(MilestoneWebhookReceived $event): bool
    {
        $payload = $event->payload;

        $milestoneData = $payload->milestone;
        $repositoryData = $payload->repository;


        return true;
    }
}
