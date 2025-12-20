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

        PullRequestReview::updateOrCreate(
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

        $last_state_before_dismiss = null;
        $incomingState = strtolower($reviewData->state);
        $stateToStore = $incomingState;

        // Check if this user is in the PR's requested_reviewers list
        $isInRequestedReviewers = collect($prData->requested_reviewers ?? [])->pluck('id')->contains($userData->id);

        // Track data to update
        $updateData = ['state' => $incomingState];

        if ($existingReviewer) {
            $last_state_before_dismiss = $existingReviewer->state;
        }

        // Match GitHub's behavior:
        // - If dismissed, save previous state and set to pending
        // - If commenting, check if there's a blocking state (original or previous)
        // - If re-requested, they go back to pending
        if ($incomingState === 'dismissed') {
            // Save the current state before dismissal so we can restore it later
            $stateToStore = 'pending';
            $updateData['state'] = 'pending';
        } elseif ($incomingState === 'commented') {
            // If they have a blocking state (either current or from before dismissal), maintain it
            if ($existingReviewer) {
                // Check if they had a blocking state before dismissal
                if (in_array($existingReviewer->last_state_before_dismiss, PullRequestReview::ABSOLUTE_ANSWERS)) {
                    // Restore the blocking state instead of changing to commented
                    $incomingState = $existingReviewer->last_state_before_dismiss;
                    $updateData['state'] = $incomingState;
                } elseif (in_array($existingReviewer->state, PullRequestReview::ABSOLUTE_ANSWERS)) {
                    // They have a blocking state currently, don't let comment overwrite it
                    $updateData['state'] = $existingReviewer->state;
                } else {
                    // No blocking state, allow the comment
                    $updateData['state'] = 'commented';
                }
            } else {
                // No existing reviewer, just set to commented
                $updateData['state'] = 'commented';
            }
        } elseif ($isInRequestedReviewers && $incomingState !== 'commented') {
            // Only force pending if not commenting and in requested_reviewers
            $incomingState = 'pending';
            $updateData['state'] = 'pending';
        }

        $updateData['last_state_before_dismiss'] = $last_state_before_dismiss;
        $updateData['state'] = $stateToStore;

        RequestedReviewer::updateOrCreate(
            [
                'pull_request_id' => $prData->id,
                'user_id' => $userData->id,
            ],
            $updateData
        );

        return true;
    }
}
