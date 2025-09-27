<?php

namespace App\Listeners;

use App\Events\IssueWebhookReceived;
use App\Models\GithubUser;
use App\Models\Issue;
use App\Models\Repository;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessIssueWebhook implements ShouldQueue
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
    public function handle(IssueWebhookReceived $event): bool
    {
        $payload = $event->payload;

        if (!$payload || !isset($payload->issue) || !isset($payload->repository)) {
            return false;
        }
        $issueData = $payload->issue;
        $repositoryData = $payload->repository;

        $userData = $issueData->user;

        // Ensure repository exists first
        $repository = self::update_repo($repositoryData);

        // Create/update the user who opened the issue
        GithubUser::updateOrCreate(
            ['github_id' => $userData->id],
            [
                'login' => $userData->login,
                'name' => $userData->name ?? $userData->login,
                'avatar_url' => $userData->avatar_url ?? '',
                'type' => $userData->type ?? 'User',
            ]
        );

        $assigneeGithubIds = [];
        // We have to loop over the assignees to create/update them in the github_users table
        if (!empty($issueData->assignees) && is_array($issueData->assignees)) {
            foreach ($issueData->assignees as $assignee) {
                $assigneeGithubIds[] = $assignee->id;

                // Create/update the assignee in github_users table
                GithubUser::updateOrCreate(
                    ['github_id' => $assignee->id],
                    [
                        'login' => $assignee->login,
                        'name' => $assignee->name ?? $assignee->login,
                        'avatar_url' => $assignee->avatar_url ?? '',
                        'type' => $assignee->type ?? 'User',
                    ]
                );
            }
        }

        $issue = Issue::updateOrCreate(
            ['github_id' => $issueData->id],
            [
                'repository_id' => $repository->github_id,
                'opened_by_id' => $userData->id,
                'number' => $issueData->number,
                'title' => $issueData->title,
                'body' => $issueData->body ?? '',
                'state' => $issueData->state,
                'labels' => json_encode($issueData->labels ?? []),
            ]);

        // Sync assignees in the pivot table
        $issue->assignees()->sync($assigneeGithubIds);

        return true;
    }

    public static function update_repo($repo)
    {
        return Repository::updateOrCreate(
            ['github_id' => $repo->id],
            [
                'organization_id' => $repo->owner->id,
                'name' => $repo->name,
                'full_name' => $repo->full_name,
                'private' => $repo->private,
                'description' => $repo->description ?? '',
                'last_updated' => now(),
            ]
        );
    }
}
