<?php

namespace App\Http\Controllers;

use App\Models\PullRequestComment;
use App\Models\Repository;
use App\Models\GithubUser;
use App\Models\PullRequest;
use App\Models\RequestedReviewer;
use App\Models\PullRequestReview;
use Illuminate\Http\Request;
use App\Events\IssueWebhookReceived;
use App\Events\PullRequestWebhookReceived;
use App\Events\CommentWebhookReceived;

class IncomingWebhookController extends Controller
{
    public function index(Request $request)
    {
        $headers = $request->headers->all();
        $raw = $request->getContent();
        // Support HTML form testing where JSON is sent as a field
        if ($request->has('payload') && (!empty($request->input('payload')))) {
            $raw = $request->input('payload');
        }
        $payload = json_decode($raw ?: '{}');

        // Allow overriding event via form/query as well
        $eventType = $headers['x-github-event'][0] ?? $request->input('x_github_event', $request->input('event', 'unknown'));

        if ($eventType === "issues") {
            IssueWebhookReceived::dispatch($payload);
        }

        if ($eventType === "issue_comment") {
            CommentWebhookReceived::dispatch($payload);
        }

        if ($eventType === "pull_request") {
            PullRequestWebhookReceived::dispatch($payload);
        }

        if ($eventType === "pull_request_review") {
            $this->pullRequestReview((array)$payload);
        }

        if ($eventType === "pull_request_review_comment") {
            $this->pullRequestReviewComment($payload);
        }

        return response()->json(['message' => 'received', 'event' => $eventType], 200);
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

    public static function pullRequestReviewComment($payload) 
    {
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
                'diff_hunk' => $commentData->diff_hunk ?? '',
                'line_start' => $commentData->start_line ?? null,
                'line_end' => $commentData->line ?? null,
                'path' => $commentData->path ?? '',
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

    public function pullRequestReview($payload)
    {
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
}
