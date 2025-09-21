<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\Repository;
use Illuminate\Http\Request;

class IncomingWebhookController extends Controller
{
    public $ISSUE_RELATED = ['issues'];

    public $ISSUE_COMMENT_RELATED = ['issue_comment'];

    public function index(Request $request)
    {
        $headers = $request->headers->all();
        $payload = $request->getContent();
        $payload = json_decode($payload ?? '{}');

        $eventType = $headers['x-github-event'][0] ?? 'unknown';

        if (in_array($eventType, $this->ISSUE_RELATED)) {
            $this->issue($payload);
        }

        if (in_array($eventType, $this->ISSUE_COMMENT_RELATED)) {
            $this->comment($payload);
        }

        return response()->json(['message' => 'received'], 200);
    }

    public function issue($payload)
    {
        $issueData = $payload->issue;
        $repositoryData = $payload->repository;

        $userData = $issueData->user;

        // Ensure repository exists first
        $repository = self::update_repo($repositoryData);

        $assigneeIds = [];
        // We have to loop over the assignees, to only store their IDs instead of full objects
        foreach ($issueData->assignees as $assignee) {
            $assigneeIds[] = $assignee->id;
        }

        Issue::updateOrCreate(
            ['github_id' => $issueData->id],
            [
                'repository_id' => $repository->id,
                'opened_by_id' => $userData->id,
                'number' => $issueData->number,
                'title' => $issueData->title,
                'body' => $issueData->body ?? '',
                'state' => $issueData->state,
                'labels' => json_encode($issueData->labels ?? []),
                'assignees' => json_encode($assigneeIds),
            ]);

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

    public function comment($payload)
    {
        $commentData = $payload->comment;
        $issueData = $payload->issue;
        $repositoryData = $payload->repository;

        $userData = $commentData->user;

        // Ensure repository exists first
        self::update_repo($repositoryData);

        // Ensure issue exists first
        $issue = Issue::where('github_id', $issueData->id)->first();
        if (! $issue) {
            // If the issue doesn't exist, we can't add a comment to it
            self::issue($payload);
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
}
