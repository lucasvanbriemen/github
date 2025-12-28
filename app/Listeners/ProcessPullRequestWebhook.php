<?php

namespace App\Listeners;

use App\Events\PullRequestWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Models\GithubUser;
use App\Models\RequestedReviewer;
use App\Models\Notification;
use App\Helpers\ApiHelper;
use App\GithubConfig;
use RuntimeException;
use Carbon\Carbon;

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

        $prData = $payload->pull_request;
        $repositoryData = $payload->repository;

        $userData = $prData->user ?? null;

        // Ensure repository exists first
        $repository = Repository::updateFromWebhook($repositoryData);

        // Create/update the user who opened the pull request
        GithubUser::updateFromWebhook($userData);

        $state = $prData->state;
        if ($prData->draft == true) {
            $state = 'draft';
        }
        
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
        $baseSha = $prData->base->sha ?? null;

        $ownerName = $repositoryData->owner->login ?? null;
        $repoName = $repositoryData->name ?? null;

        if ($ownerName && $repoName) {
            // Prefer comparing by SHAs to avoid failures after merges or branch deletions
            if ($baseSha && $headSha) {
                $compareData = ApiHelper::githubApi("/repos/{$ownerName}/{$repoName}/compare/{$baseSha}...{$headSha}");
            } elseif (isset($prData->base->ref, $prData->head->ref)) {
                $compareData = ApiHelper::githubApi("/repos/{$ownerName}/{$repoName}/compare/{$prData->base->ref}...{$prData->head->ref}");
            } else {
                $compareData = null;
            }

            if ($compareData && isset($compareData->merge_base_commit->sha)) {
                $mergeBaseSha = $compareData->merge_base_commit->sha;
            }
        }

        // Update base fields in items table
        $pr = PullRequest::updateOrCreate(
            ['id' => $prData->id],
            [
                'repository_id' => $repository->id,
                'opened_by_id' => $userData->id,
                'number' => $prData->number,
                'title' => $prData->title,
                'body' => $prData->body ?? '',
                'state' => $state,
                'labels' => json_encode($prData->labels ?? []),
            ]
        );

        // Update PR-specific fields in pull_requests table
        \DB::table('pull_requests')->updateOrInsert(
            ['id' => $prData->id],
            [
                'head_branch' => $prData->head->ref,
                'head_sha' => $headSha,
                'base_branch' => $prData->base->ref,
                'merge_base_sha' => $mergeBaseSha,
                'closed_at' => $closedAt,
                'updated_at' => now(),
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

        // Sync assignees (now uses issue_assignees table for both issues and PRs)
        if ($pr) {
            // Get old assignee IDs before syncing
            $oldAssigneeIds = $pr->assignees()->pluck('github_users.id')->toArray();

            // Sync assignees in the pivot table
            $pr->assignees()->sync($assigneeGithubIds);

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
                        'related_id' => $pr->id,
                        'actor_id' => $actorId,
                        'repository_id' => $repository->id,
                        'metadata' => json_encode([
                            'item_number' => $pr->number,
                            'item_type' => 'pull_request',
                            'item_title' => $pr->title,
                        ])
                    ]);
                }
            }
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
                    'user_id' => $reviewerData->id,
                    'state' => 'pending'
                ]
            );
        }

        return true;
    }
}
