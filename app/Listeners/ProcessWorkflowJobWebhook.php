<?php

namespace App\Listeners;

use App\Events\WorkflowJobWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Commit;
use App\Models\GithubUser;
use App\Models\Branch;
use App\Models\Repository;

class ProcessWorkflowJobWebhook // implements ShouldQueue
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
    public function handle(WorkflowJobWebhookReceived $event)
    {
        $payload = $event->payload;
    }
}
