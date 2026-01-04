<?php

namespace App\Listeners;

use App\Events\PullRequestReviewCommentWebhookReceived;
use App\Events\PullRequestUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\PullRequest;
use App\Models\PullRequestComment;
use App\Models\BaseComment;
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

        $baseComment = BaseComment::updateOrCreate(
            ['comment_id' => $commentData->id],
            [
                'issue_id' => $prData->id,
                'user_id' => $userData->id,
                'body' => $commentData->body ?? '',
                'type' => 'code',
            ]
        );

        $sideValue = $commentData->side ?? 'RIGHT';

        $comment = PullRequestComment::updateOrCreate(
            ['id' => $commentData->id],
            [
                'pull_request_id' => $prData->id,
                'base_comment_id' => $baseComment->id,
                'in_reply_to_id' => $commentData->in_reply_to_id ?? null,
                'diff_hunk' => $commentData->diff_hunk ?? '',
                'line_start' => $commentData->start_line ?? null,
                'line_end' => $commentData->line ?? null,
                'path' => $commentData->path ?? '',
                'side' => $sideValue,
                'original_line' => $commentData->original_line ?? null,
                'pull_request_review_id' => $commentData->pull_request_review_id ?? null,
            ]
        );

        // If action is deleted, we just delete the comment
        if ($payload->action === 'deleted') {
            PullRequestComment::where('id', $commentData->id)->delete();
        } else {
            // Broadcast code comment update (skip deletion events)
            event(new PullRequestUpdated(
                $pr,
                'code_comment',
                [
                    'comment_id' => $comment->id,
                    'commenter' => $userData->login ?? 'Unknown',
                    'path' => $commentData->path ?? '',
                ]
            ));
        }

        return true;
    }
}
