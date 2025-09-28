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

        $reviewData = (object)$payload['review'];
        $prData = (object)$payload['pull_request'];

        $review = PullRequestReview::updateOrCreate([
            'id' => $reviewData->id,
        ], [
            'pull_request_id' => $prData->id,
            'user_id' => $reviewData->user->id,
            'body' => $reviewData->body,
            'state' => $reviewData->state,
        ]);

        // We also need to create/update RequestedReviewer
        $repositoryData = $payload['repository'];
        $repository = self::update_repo((object)$repositoryData);
        $userData = (object)$reviewData->user;
        self::ensureGithubUser($userData);
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
