<?php

namespace App\Listeners;

use App\Events\WorkflowRunWebhookReceived;
use App\Models\Commit;
use App\Models\Workflow;

class ProcessWorkflowRunWebhook // implements ShouldQueue
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
    public function handle(WorkflowRunWebhookReceived $event)
    {
        $payload = $event->payload;

        $workflow = $payload->workflow_run;

        $id = $workflow->id;
        $name = $workflow->name;
        $state = $workflow->status;
        $conclusion = $workflow->conclusion;
        $head_sha = $workflow->head_sha ?? null;

        $workflow = Workflow::updateOrCreate(
            ['id' => $id],
            [
                'name' => $name,
                'state' => $state,
                'conclusion' => $conclusion
            ]
        );

        if ($head_sha) {
            Commit::where('sha', $head_sha)->update(['workflow_id' => $workflow->id]);
        }

        return true;
    }
}
