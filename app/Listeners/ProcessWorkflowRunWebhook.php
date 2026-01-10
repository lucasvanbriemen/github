<?php

namespace App\Listeners;

use App\Events\WorkflowRunWebhookReceived;
use App\Models\Commit;
use App\Models\Workflow;
use App\Models\PullRequest;
use App\Models\Notification;
use App\GithubConfig;

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

        // Create notification if workflow failed on a PR where user is assigned
        if ($conclusion === 'failed' && $head_sha) {
            $prs = PullRequest::whereHas('details', function ($query) use ($head_sha) {
                $query->where('head_sha', $head_sha);
            })->get();

            foreach ($prs as $pr) {
                if ($pr->isCurrentlyAssignedToUser()) {
                    // Don't create notification if actor is the configured user
                    if ($payload->sender?->id === GithubConfig::USERID) {
                        break;
                    }

                    // Avoid duplicate notifications for the same PR workflow failure
                    if (Notification::where('type', 'workflow_failed')
                        ->where('related_id', $pr->id)
                        ->exists()) {
                        break;
                    }

                    Notification::create([
                        'type' => 'workflow_failed',
                        'related_id' => $pr->id,
                        'triggered_by_id' => $payload->sender?->id
                    ]);
                    break; // Only create one notification per workflow run
                }
            }
        }

        return true;
    }
}
