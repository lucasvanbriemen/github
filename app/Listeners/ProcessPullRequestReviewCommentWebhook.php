<?php

namespace App\Listeners;

use App\Events\PullRequestReviewCommentWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\PullRequest;
use App\Models\PullRequestComment;
use App\Models\Repository;
use App\Models\GithubUser;

class ProcessPullRequestReviewCommentWebhook implements ShouldQueue
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
    public function handle(PullRequestReviewCommentWebhookReceived $event): bool
    {
        $payload = $event->payload;

        if (!$payload || !isset($payload->comment) || !isset($payload->pull_request) || !isset($payload->repository)) {
            return false;
        }
        $commentData = $payload->comment;
        $prData = $payload->pull_request;
        $repositoryData = $payload->repository;

        $userData = $commentData->user ?? null;

        // Ensure repository exists first
        $repository = self::update_repo($repositoryData);
        $pr = PullRequest::where('github_id', $prData->id)->first();
        if (! $pr) {
            // If the PR doesn't exist, we can't add a comment to it
            return false;
        }

        // Ensure user exists first
        $user = self::ensureGithubUser($userData);

        // Create comment
        PullRequestComment::updateOrCreate(
            ['id' => $commentData->id],
            [
                'pull_request_id' => $prData->id,
                'user_id' => $userData->id,
                'body' => $commentData->body ?? '',
                'in_reply_to_id' => $commentData->in_reply_to_id ?? null,
                'diff_hunk' => $commentData->diff_hunk ?? '',
                'line_start' => $commentData->start_line ?? null,
                'line_end' => $commentData->line ?? null,
                'path' => $commentData->path ?? '',
            ]
        );

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
