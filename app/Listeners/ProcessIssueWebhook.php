<?php

namespace App\Listeners;

use App\Events\IssuesWebhookReceived;
use App\Models\GithubUser;
use App\Models\Issue;
use App\Models\Repository;
use App\Models\Notification;
use App\GithubConfig;

class ProcessIssueWebhook
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
    public function handle(IssuesWebhookReceived $event): bool
    {
        $payload = $event->payload;

        if (!$payload || !isset($payload->issue) || !isset($payload->repository)) {
            return false;
        }
        $issueData = $payload->issue;
        $repositoryData = $payload->repository;

        $userData = $issueData->user;

        $repository = Repository::updateFromWebhook($repositoryData);

        // Issue author
        GithubUser::updateFromWebhook($userData);

        $assigneeGithubIds = [];
        // We have to loop over the assignees to create/update them in the github_users table
        if (!empty($issueData->assignees) && is_array($issueData->assignees)) {
            foreach ($issueData->assignees as $assignee) {
                $assigneeGithubIds[] = $assignee->id;

                // Create/update the assignee in github_users table
                GithubUser::updateFromWebhook($assignee);
            }
        }

        // If its a pull request, we ignore it
        if (isset($issueData->pull_request)) {
            return true;
        }

        // check if the issue already exists
        $issue = Issue::where('id', $issueData->id)->first();
        if (!$issue) {
            $preHookAssigned = false;
        } else {
            $preHookAssigned = $issue->isCurrentlyAssignedToUser();
        }

        $issue = Issue::updateOrCreate(
            ['id' => $issueData->id],
            [
                'repository_id' => $repository->id,
                'opened_by_id' => $userData->id,
                'number' => $issueData->number,
                'title' => $issueData->title,
                'body' => $issueData->body ?? '',
                'milestone_id' => $issueData->milestone->id ?? null,
                'state' => $issueData->state,
                'labels' => json_encode($issueData->labels ?? []),
            ]
        );

        // Sync assignees in the pivot table
        $issue->assignees()->sync($assigneeGithubIds);

        $currentlyAssigned = $issue->isCurrentlyAssignedToUser();
        if ($currentlyAssigned && !$preHookAssigned) {
            $senderData = $payload->sender ?? null;
            if ($senderData) {
                GithubUser::updateFromWebhook($senderData);
            }

            // Don't create notification if actor is the configured user
            if ($senderData?->id === GithubConfig::USERID) {
                // Continue processing but skip notification
            } elseif (!Notification::where('type', 'item_assigned')
                ->where('related_id', $issue->id)
                ->exists()) {
                Notification::create([
                    'type' => 'item_assigned',
                    'related_id' => $issue->id,
                    'triggered_by_id' => $senderData?->id
                ]);
            }
        }

        return true;
    }
}
