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
        RequestedReviewer::updateOrCreate(
            [
                'pull_request_id' => $prData->id,
                'user_id' => $userData->id,
            ],
            [
                'state' => $reviewData->state,
            ]
        );

        // im not assisned to the pr (or the author) dont send the email
        if ($prData->user->id !== GithubConfig::USERID && !collect($prData->requested_reviewers)->pluck('id')->contains(GithubConfig::USERID) && $reviewData->pull_request->id !== GithubConfig::USERID) {
            return true;
        }

        // After 1 minute send out the email, this is to ensure that all comments are created first
        Mail::to(GithubConfig::USER_EMAIL)
            ->send(new PullRequestReviewed($pullRequestReview));

        return true;
    }
}
