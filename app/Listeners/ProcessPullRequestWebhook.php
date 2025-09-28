<?php

namespace App\Listeners;

use App\Events\PullRequestWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Models\GithubUser;
use App\Models\RequestedReviewer;

class ProcessPullRequestWebhook implements ShouldQueue
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
    public function handle(PullRequestWebhookReceived $event): bool
    {
        $payload = $event->payload;

        if (!$payload || !isset($payload->pull_request) || !isset($payload->repository)) {
            return false;
        }
        $prData = $payload->pull_request;
        $repositoryData = $payload->repository;

        $userData = $prData->user ?? null;

        // Ensure repository exists first
        $repository = self::update_repo($repositoryData);

        // Create/update the user who opened the pull request
        self::ensureGithubUser($userData);

        PullRequest::updateOrCreate(
            ['github_id' => $prData->id],
            [
                'repository_id' => $repository->github_id,
                'opened_by_id' => $userData->id,
                'number' => $prData->number,
                'title' => $prData->title,
                'body' => $prData->body ?? '',
                'state' => $prData->state,
            ]
        );

        // Sync assignees in the pivot table
        $assigneeGithubIds = [];
        if (!empty($prData->assignees) && is_array($prData->assignees)) {
            foreach ($prData->assignees as $assignee) {
                $assigneeGithubIds[] = $assignee->id;

                // Create/update the assignee in github_users table
                self::ensureGithubUser($assignee);
            }
        }

        $pr = PullRequest::where('github_id', $prData->id)->first();
        if ($pr) {
            $pr->assignees()->sync($assigneeGithubIds);
        }

        if ($payload->action === 'review_requested') {
            // Create/update the requested reviewer in github_users table
            $reviewerData = $payload->requested_reviewer ?? null;
            self::ensureGithubUser($reviewerData);

            RequestedReviewer::updateOrCreate(
                [
                    'pull_request_id' => $prData->id,
                    'user_id' => $reviewerData->id,
                ],
                [
                    'pull_request_id' => $prData->id,
                    'user_id' => $reviewerData->id
                ]
            );
           
        }

        return true;
    }

    protected static function ensureGithubUser($userData)
    {
        if (! $userData) {
            return null;
        }

        return GithubUser::updateOrCreate(
            ['github_id' => $userData->id],
            [
                'login' => $userData->login ?? ($userData->name ?? ''),
                'name' => $userData->name ?? $userData->login ?? '',
                'avatar_url' => $userData->avatar_url ?? null,
                'type' => $userData->type ?? 'User',
            ]
        );
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
