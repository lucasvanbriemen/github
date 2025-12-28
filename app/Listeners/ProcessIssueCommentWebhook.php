<?php

namespace App\Listeners;

use App\Events\IssueCommentWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Issue;
use App\Models\BaseComment;
use App\Models\Repository;
use App\Models\GithubUser;
use App\Models\PullRequest;
use App\Models\Notification;
use App\Models\Item;
use App\Events\IssuesWebhookReceived;
use App\Events\PullRequestWebhookReceived;
use App\GithubConfig;

class ProcessIssueCommentWebhook implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public function handle(IssueCommentWebhookReceived $event): bool
    {
        $payload = $event->payload;

        $commentData = $payload->comment;
        $issueData = $payload->issue;
        $repositoryData = $payload->repository;

        $userData = $commentData->user ?? null;

        // Ensure repository exists first
        $repository = Repository::updateFromWebhook($repositoryData);

        // PR needs some special handling since the normal issue ID is not the same as the PR ID
        if (isset($issueData->pull_request)) {
            // Get the ID where the issue number and repository matching
            $pr = PullRequest::where('number', $issueData->number)
                ->where('repository_id', $repository->id)
                ->first();

            if (! $pr) {
                // If PR doesn't exist, we need to create it via the PR webhook, not the issue webhook
                // Create a mock PR webhook payload
                $prPayload = (object) [
                    'action' => 'opened',
                    'pull_request' => $issueData,
                    'repository' => $repositoryData,
                ];
                PullRequestWebhookReceived::dispatch($prPayload);
                return false;
            }

            $issueData->id = $pr->id;
        } else {
            // Ensure issue exists first (only for actual issues, not PRs)
            $issue = Issue::where('id', $issueData->id)->first();
            if (! $issue) {
                // If the issue doesn't exist, we can't add a comment to it
                IssuesWebhookReceived::dispatch($payload);
                return false;
            }
        }

        GithubUser::updateFromWebhook($userData);

        BaseComment::updateOrCreate(
            ['comment_id' => $commentData->id, 'type' => 'issue'],
            [
                'comment_id' => $commentData->id,
                'issue_id' => $issueData->id,
                'user_id' => $userData->id,
                'body' => $commentData->body ?? '',
                'type' => 'issue',
            ]
        );

        // Create activity notification if current user is assigned to this item
        $item = Item::find($issueData->id);
        if ($item) {
            $actorId = $userData->id;

            // Check if current user is assigned to this item
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
                        'repository_id' => $repository->id,
                        'metadata' => json_encode([
                            'item_number' => $item->number,
                            'item_type' => $item->type ?? 'issue',
                            'activity_type' => 'comment',
                            'comment_id' => $commentData->id,
                        ])
                    ]);
                }
            }
        }

        return true;
    }
}
