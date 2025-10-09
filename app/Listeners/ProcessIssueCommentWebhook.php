<?php

namespace App\Listeners;

use App\Events\IssueCommentWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\Repository;
use App\Models\PullRequest;
use App\Events\IssuesWebhookReceived;

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

        // Ensure issue exists first
        $issue = Issue::where('id', $issueData->id)->first();
        if (! $issue) {
            // If the issue doesn't exist, we can't add a comment to it
            IssuesWebhookReceived::dispatch($payload);
        }

        // PR needs some special handling since the normal issue ID is not the same as the PR ID
        if (isset($issueData->pull_request)) {
            // Get the ID where the issue number and repository matching
            $pr = PullRequest::where('number', $issueData->number)
                ->where('repository_id', $repository->id);
            $issueData->id = $pr->first()->id ?? null;
        }

        IssueComment::updateOrCreate(
            ['id' => $commentData->id],
            [
                'issue_id' => $issueData->id,
                'user_id' => $userData->id,
                'body' => $commentData->body ?? '',
            ]
        );

        return true;
    }
}
