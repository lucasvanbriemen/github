<?php

namespace App\Listeners;

use App\Events\PullRequestReviewCommentWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\PullRequest;
use App\Models\PullRequestComment;
use App\Models\BaseComment;
use App\Models\Repository;
use App\Models\GithubUser;
use App\Models\Notification;
use App\Models\Item;
use App\GithubConfig;

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

        PullRequestComment::updateOrCreate(
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

        // Create activity notification if current user is assigned to this PR (only for create/edit, not delete)
        if ($payload->action !== 'deleted' && $pr) {
            $item = Item::find($prData->id);
            if ($item) {
                $actorId = $userData->id;

                // Check if current user is assigned to this PR
                $isUserAssigned = $item->assignees()
                    ->where('user_id', GithubConfig::USERID)
                    ->exists();

                // Skip if actor is current user
                if ($isUserAssigned && $actorId != GithubConfig::USERID) {
                    // Check for duplicate notification within 5 minutes
                    $existingNotification = Notification::where('type', 'activity_on_assigned_item')
                        ->where('related_id', $item->id)
                        ->where('actor_id', $actorId)
                        ->where('created_at', '>', now()->subMinutes(5))
                        ->first();

                    if (!$existingNotification) {
                        Notification::create([
                            'type' => 'activity_on_assigned_item',
                            'related_id' => $item->id,
                            'actor_id' => $actorId,
                            'repository_id' => $item->repository_id,
                            'metadata' => json_encode([
                                'item_number' => $item->number,
                                'item_type' => 'pull_request',
                                'activity_type' => 'review_comment',
                                'comment_id' => $commentData->id,
                            ])
                        ]);
                    }
                }
            }
        }

        // If action is deleted, we just delete the comment
        if ($payload->action === 'deleted') {
            PullRequestComment::where('id', $commentData->id)->delete();
        }

        return true;
    }
}
