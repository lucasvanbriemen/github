<?php

namespace App\Listeners;

use App\Events\PullRequestWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Models\GithubUser;
use App\Models\RequestedReviewer;
use App\Helpers\ApiHelper;

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

        if ($state === 'closed' && isset($prData->closed_at)) {
            $closedAt = $prData->closed_at;
        } else {
            $closedAt = null;
        }

        // If we merge a PR we cant just use the diff anymore since both versions contain the same changes
        // We need to get the diff between the merge base and the head commit, so we store the sha of both to compare instead of the base and head branch names
        $mergeBaseSha = null;
        $headSha = $prData->head->sha ?? null;

        if ($headSha && isset($prData->base->ref)) {
            $token = config('services.github.access_token');
            $ownerName = $repositoryData->owner->login ?? null;
            $repoName = $repositoryData->name ?? null;

            if ($ownerName && $repoName) {
                $url = "https://api.github.com/repos/{$ownerName}/{$repoName}/compare/{$prData->base->ref}...{$prData->head->ref}";

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $token,
                    'Accept: application/vnd.github+json',
                    'User-Agent: github-gui',
                    'X-GitHub-Api-Version: 2022-11-28'
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);

                $response = curl_exec($ch);
                curl_close($ch);

                if ($response) {
                    $compareData = json_decode($response);
                    if (isset($compareData->merge_base_commit->sha)) {
                        $mergeBaseSha = $compareData->merge_base_commit->sha;
                    }
                }
            }
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
                'head_sha' => $headSha,
                'base_branch' => $prData->base->ref,
                'merge_base_sha' => $mergeBaseSha,
                'closed_at' => $closedAt,
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
