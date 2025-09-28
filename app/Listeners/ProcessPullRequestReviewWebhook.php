<?php

namespace App\Listeners;

use App\Events\PullRequestReviewWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\PullRequestReview;
use App\Models\Repository;
use App\Models\GithubUser;
use App\Models\RequestedReviewer;

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
    public function handle(PullRequestReviewWebhookReceived $event): void
    {
        $payload = $event->payload;

        $reviewData = $payload->review;
        $prData = $payload->pull_request;
        
        $repositoryData = $payload->repository;
        Repository::updateFromWebhook($repositoryData);

        $userData = $reviewData->user;
        GithubUser::updateFromWebhook($userData);

        PullRequestReview::updateOrCreate([
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
    }
}
