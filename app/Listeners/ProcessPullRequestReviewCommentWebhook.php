<?php

namespace App\Listeners;

use App\Events\PullRequestReviewCommentWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\PullRequest;
use App\Models\PullRequestComment;
use App\Models\Repository;
use App\Models\GithubUser;

class ProcessPullRequestReviewCommentWebhook
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

        $commentData = $payload->comment;
        $prData = $payload->pull_request;
        $userData = $commentData->user ?? null;

        $repositoryData = $payload->repository;
        Repository::updateFromWebhook($repositoryData);

        $pr = PullRequest::where('id', $prData->id)->first();
        if (! $pr) {
            // If the PR doesn't exist, we can't add a comment to it
            return false;
        }

        GithubUser::updateFromWebhook($userData);

        $sideValue = $commentData->side ?? 'RIGHT';

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
                'side' => $sideValue,
                'original_line' => $commentData->original_line ?? null,
            ]
        );

        return true;
    }
}
