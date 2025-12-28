<?php

namespace App\Listeners;

use App\Events\WorkflowRunWebhookReceived;
use App\Models\Commit;
use App\Models\Workflow;
use App\Models\PullRequest;
use App\Models\Item;
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

        // Create notification for workflow failures on assigned PRs
        if ($conclusion === 'failure') {
            $prIds = [];

            // Try to get PRs from the webhook payload first (most reliable)
            if (isset($payload->workflow_run->pull_requests) && is_array($payload->workflow_run->pull_requests)) {
                foreach ($payload->workflow_run->pull_requests as $pr) {
                    $prIds[] = $pr->id;
                }
            } elseif ($head_sha) {
                // Fallback: Find PR by matching head_sha
                $prs = Item::join('pull_requests as pr_details', 'items.id', '=', 'pr_details.id')
                    ->where('pr_details.head_sha', $head_sha)
                    ->where('items.state', '!=', 'merged')
                    ->select('items.id')
                    ->get();

                foreach ($prs as $pr) {
                    $prIds[] = $pr->id;
                }
            }

            // Create notifications for each PR where current user is assigned
            $actorId = $payload->sender->id ?? null;

            foreach ($prIds as $prId) {
                $pr = Item::find($prId);
                if (!$pr) {
                    continue;
                }

                // Check if current user is assigned to this PR
                $isUserAssigned = $pr->assignees()
                    ->where('user_id', GithubConfig::USERID)
                    ->exists();

                if (!$isUserAssigned) {
                    continue;
                }

                // Check for existing notification for this workflow
                $existingNotification = Notification::where('type', 'workflow_failed')
                    ->where('related_id', $workflow->id)
                    ->where('completed', false)
                    ->first();

                if (!$existingNotification) {
                    Notification::create([
                        'type' => 'workflow_failed',
                        'related_id' => $workflow->id,
                        'actor_id' => $actorId,
                        'repository_id' => $pr->repository_id,
                        'metadata' => json_encode([
                            'workflow_name' => $name,
                            'pr_id' => $prId,
                            'pr_number' => $pr->number,
                            'pr_title' => $pr->title,
                        ])
                    ]);
                }
            }
        }

        return true;
    }
}
