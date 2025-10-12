<?php

namespace App\Listeners;

use App\Events\PullRequestWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Models\GithubUser;
use App\Models\RequestedReviewer;

class ProcessPullRequestWebhook //implements ShouldQueue
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
        $repository = Repository::updateFromWebhook($repositoryData);

        // Create/update the user who opened the pull request
        GithubUser::updateFromWebhook($userData);

        $state = $prData->state;
        if ($state === 'closed' && isset($prData->merged_at) && $prData->merged_at !== null) {
            $state = 'merged';
        }

        PullRequest::updateOrCreate(
            ['id' => $prData->id],
            [
                'repository_id' => $repository->id,
                'opened_by_id' => $userData->id,
                'number' => $prData->number,
                'title' => $prData->title,
                'body' => $prData->body ?? '',
                'state' => $state,
                'head_branch' => $prData->head->ref,
                'base_branch' => $prData->base->ref,
            ]
        );

        // Sync assignees in the pivot table
        $assigneeGithubIds = [];
        if (!empty($prData->assignees) && is_array($prData->assignees)) {
            foreach ($prData->assignees as $assignee) {
                $assigneeGithubIds[] = $assignee->id;

                // Create/update the assignee in github_users table
                GithubUser::updateFromWebhook($assignee);
            }
        }

        $pr = PullRequest::where('id', $prData->id)->first();
        if ($pr) {
            $pr->assignees()->sync($assigneeGithubIds);
        }

        if ($payload->action === 'review_requested') {
            // Create/update the requested reviewer in github_users table
            $reviewerData = $payload->requested_reviewer ?? null;
            GithubUser::updateFromWebhook($reviewerData);

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
}
