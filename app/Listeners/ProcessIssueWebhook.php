<?php

namespace App\Listeners;

use App\Events\IssuesWebhookReceived;
use App\Models\GithubUser;
use App\Models\Issue;
use App\Models\Repository;
use App\Models\Notification;
use App\GithubConfig;
use Illuminate\Contracts\Queue\ShouldQueue;

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

        $issue = Issue::updateOrCreate(
            ['id' => $issueData->id],
            [
                'repository_id' => $repository->id,
                'opened_by_id' => $userData->id,
                'number' => $issueData->number,
                'title' => $issueData->title,
                'body' => $issueData->body ?? '',
                'state' => $issueData->state,
                'labels' => json_encode($issueData->labels ?? []),
            ]);

        // Get old assignee IDs before syncing
        $oldAssigneeIds = $issue->assignees()->pluck('github_users.id')->toArray();

        // Sync assignees in the pivot table
        $issue->assignees()->sync($assigneeGithubIds);

        // Detect newly assigned users and create notifications
        $newAssigneeIds = array_diff($assigneeGithubIds, $oldAssigneeIds);
        $actorId = $payload->sender->id ?? null;

        foreach ($newAssigneeIds as $assigneeId) {
            // Skip if user assigned themselves
            if ($assigneeId == GithubConfig::USERID && $assigneeId == $actorId) {
                continue;
            }

            // Only create notification if current user is assigned
            if ($assigneeId == GithubConfig::USERID) {
                Notification::create([
                    'type' => 'assigned_to_item',
                    'related_id' => $issue->id,
                    'actor_id' => $actorId,
                    'repository_id' => $repository->id,
                    'metadata' => json_encode([
                        'item_number' => $issue->number,
                        'item_type' => $issue->type ?? 'issue',
                        'item_title' => $issue->title,
                    ])
                ]);
            }
        }

        return true;
    }
}
