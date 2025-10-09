<?php

namespace App\Listeners;

use App\Events\IssuesWebhookReceived;
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

        // Sync assignees in the pivot table
        $issue->assignees()->sync($assigneeGithubIds);

        return true;
    }
}
