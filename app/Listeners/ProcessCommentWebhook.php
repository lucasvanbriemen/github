<?php

namespace App\Listeners;

use App\Events\CommentWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\Repository;
use App\Models\PullRequest;
use App\Events\IssuesWebhookReceived;

class ProcessCommentWebhook implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public function handle(CommentWebhookReceived $event): bool
    {
        $payload = $event->payload;

        if (!$payload || !isset($payload->comment) || !isset($payload->issue) || !isset($payload->repository)) {
            return false;
        }
        $commentData = $payload->comment;
        $issueData = $payload->issue;
        $repositoryData = $payload->repository;

        $userData = $commentData->user ?? null;

        // Ensure repository exists first
        $repository = self::update_repo($repositoryData);

        // Ensure issue exists first
        $issue = Issue::where('github_id', $issueData->id)->first();
        if (! $issue) {
            // If the issue doesn't exist, we can't add a comment to it
            IssuesWebhookReceived::dispatch($payload);
        }

        // get the correct ID if its a PR
        if (isset($issueData->pull_request)) {
            // Get the ID where the issue number and repository matching
            $pr = PullRequest::where('number', $issueData->number)
                ->where('repository_id', $repository->github_id);
            $issueData->id = $pr->first()->github_id ?? null;
        }

        IssueComment::updateOrCreate(
            ['github_id' => $commentData->id],
            [
                'issue_github_id' => $issueData->id,
                'user_id' => $userData->id,
                'body' => $commentData->body ?? '',
            ]
        );

        return true;
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
