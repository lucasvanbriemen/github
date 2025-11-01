<?php

namespace App\Listeners;

use App\Events\PullRequestReviewWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\PullRequestReview;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Models\GithubUser;
use App\Models\RequestedReviewer;
use App\Mail\PullRequestReviewed;
use App\GithubConfig;
use Illuminate\Support\Facades\Mail;

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

        $pullRequestReview = PullRequestReview::updateOrCreate([
            'id' => $reviewData->id,
        ], [
            'pull_request_id' => $prData->id,
            'user_id' => $reviewData->user->id,
            'body' => $reviewData->body,
            'state' => $reviewData->state,
        ]);

        // We also need to create/update RequestedReviewer (since thats how we show reviews in the UI sidebar)
        // BUT: Don't let COMMENTED overwrite CHANGES_REQUESTED
        // A CHANGES_REQUESTED review blocks the PR until the reviewer APPROVEs
        // Also: If someone is in requested_reviewers, they're PENDING (their review was dismissed or they were re-requested)

        $existingReviewer = RequestedReviewer::where('pull_request_id', $prData->id)
            ->where('user_id', $userData->id)
            ->first();

        $newState = strtolower($reviewData->state);

        // Check if this user is in the PR's requested_reviewers list
        $isInRequestedReviewers = collect($prData->requested_reviewers ?? [])->pluck('id')->contains($userData->id);

        // If they're in requested_reviewers, they're pending (dismissed or re-requested)
        if ($isInRequestedReviewers) {
            $newState = 'pending';
        }
        // If dismissed, treat as pending
        elseif ($newState === 'dismissed') {
            $newState = 'pending';
        }

        $shouldUpdate = true;

        // If they previously requested changes, only update if the new state clears or updates the block
        if ($existingReviewer && $existingReviewer->state === 'changes_requested' && !$isInRequestedReviewers) {
            // Only these states should overwrite CHANGES_REQUESTED
            if (!in_array($newState, ['approved', 'changes_requested', 'pending'])) {
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

        // im not assisned to the pr (or the author) dont send the email
        // if ($prData->user->id !== GithubConfig::USERID && !collect($prData->requested_reviewers)->pluck('id')->contains(GithubConfig::USERID)) {
        //     return true;
        // }

        // // After 1 minute send out the email, this is to ensure that all comments are created first
        // Mail::to(GithubConfig::USER_EMAIL)
        //     ->later(now()->addMinute(), new PullRequestReviewed($pullRequestReview));

        return true;
    }
}
