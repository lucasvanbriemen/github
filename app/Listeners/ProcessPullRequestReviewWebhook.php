<?php

namespace App\Listeners;

use App\Events\PullRequestReviewWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\PullRequestReview;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Models\GithubUser;
use App\Models\RequestedReviewer;
use App\Models\BaseComment;

class ProcessPullRequestReviewWebhook implements ShouldQueue
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
    public function handle(PullRequestReviewWebhookReceived $event): bool
    {
        $payload = $event->payload;

        $reviewData = $payload->review;
        $prData = $payload->pull_request;

        $repositoryData = $payload->repository;
        Repository::updateFromWebhook($repositoryData);

        $userData = $reviewData->user;
        GithubUser::updateFromWebhook($userData);

        // Ensure the pull request exists before creating the review
        PullRequest::updateFromWebhook($prData);

        $baseComment = BaseComment::updateOrCreate(
            ['comment_id' => $reviewData->id, 'type' => 'review'],
            [
                'issue_id' => $prData->id,
                'user_id' => $userData->id,
                'body' => $reviewData->body ?? '',
                'type' => 'review',
            ]
        );

        $pullRequestReview = PullRequestReview::updateOrCreate(
            ['id' => $reviewData->id],
            [
                'base_comment_id' => $baseComment->id,
                'state' => $reviewData->state,
            ]
        );

        // We also need to create/update RequestedReviewer (since thats how we show reviews in the UI sidebar)
        // BUT: Don't let COMMENTED overwrite CHANGES_REQUESTED OR APPROVED
        // A CHANGES_REQUESTED review blocks the PR until the reviewer APPROVEs
        // Also: If someone is in requested_reviewers, they're PENDING (their review was dismissed or they were re-requested)

        $existingReviewer = RequestedReviewer::where('pull_request_id', $prData->id)
            ->where('user_id', $userData->id)
            ->first();

        $newState = strtolower($reviewData->state);

        // Check if this user is in the PR's requested_reviewers list
        $isInRequestedReviewers = collect($prData->requested_reviewers ?? [])->pluck('id')->contains($userData->id);

        // Match GitHub's behavior:
        // - If dismissed or re-requested, they go back to pending
        // - If commenting while pending, allow the state to be commented
        // - If they have an existing blocking state, don't let comments clear it
        if ($newState === 'dismissed') {
            $newState = 'pending';
        } elseif ($isInRequestedReviewers && $newState !== 'commented') {
            // Only force pending if not commenting (comment should update the state)
            $newState = 'pending';
        }

        $shouldUpdate = true;

        // If they previously requested changes, only update if the new state clears or updates the block
        if ($existingReviewer && $existingReviewer->state === 'changes_requested') {
            // Don't let commented state overwrite changes_requested
            if ($newState === 'commented') {
                $shouldUpdate = false;
            }
        }

        // Similarly, don't let commented overwrite approved
        if ($existingReviewer && $existingReviewer->state === 'approved') {
            if ($newState === 'commented') {
                $shouldUpdate = false;
            }
        }

        if ($shouldUpdate) {
            RequestedReviewer::updateOrCreate(
                [
                    'pull_request_id' => $prData->id,
                    'user_id' => $userData->id,
                ],
                [
                    'state' => $newState,
                ]
            );
        }

        return true;
    }
}
