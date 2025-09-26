<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\PullRequestComment;
use App\Models\Repository;
use App\Models\GithubUser;
use App\Models\PullRequest;
use App\Models\RequestedReviewer;
use App\Models\PullRequestReview;
use Illuminate\Http\Request;

class IncomingWebhookController extends Controller
{
    public $ISSUE_RELATED = ['issues'];

    public $ISSUE_COMMENT_RELATED = ['issue_comment'];
    public $PULL_REQUEST_RELATED = ['pull_request'];
    public $PULL_REQUEST_REVIEW_RELATED = ['pull_request_review'];

    public $PULL_REQUEST_REVIEW_COMMENT_RELATED = ['pull_request_review_comment'];

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

        if (in_array($eventType, $this->ISSUE_RELATED)) {
            $this->issue($payload);
        }

        if (in_array($eventType, $this->ISSUE_COMMENT_RELATED)) {
            $this->comment($payload);
        }

        if (in_array($eventType, $this->PULL_REQUEST_RELATED)) {
            $this->pullRequest($payload);
        }

        if (in_array($eventType, $this->PULL_REQUEST_REVIEW_RELATED)) {
            $this->pullRequestReview((array)$payload);
        }

        if (in_array($eventType, $this->PULL_REQUEST_REVIEW_COMMENT_RELATED)) {
            $this->pullRequestReviewComment($payload);
        }

        return response()->json(['message' => 'received', 'event' => $eventType], 200);
    }

    public function issue($payload)
    {
        if (!$payload || !isset($payload->issue) || !isset($payload->repository)) {
            return false;
        }
        $issueData = $payload->issue;
        $repositoryData = $payload->repository;

        $userData = $issueData->user;

        // Ensure repository exists first
        $repository = self::update_repo($repositoryData);

        // Create/update the user who opened the issue
        GithubUser::updateOrCreate(
            ['github_id' => $userData->id],
            [
                'login' => $userData->login,
                'name' => $userData->name ?? $userData->login,
                'avatar_url' => $userData->avatar_url ?? '',
                'type' => $userData->type ?? 'User',
            ]
        );

        $assigneeGithubIds = [];
        // We have to loop over the assignees to create/update them in the github_users table
        if (!empty($issueData->assignees) && is_array($issueData->assignees)) {
            foreach ($issueData->assignees as $assignee) {
                $assigneeGithubIds[] = $assignee->id;

                // Create/update the assignee in github_users table
                GithubUser::updateOrCreate(
                    ['github_id' => $assignee->id],
                    [
                        'login' => $assignee->login,
                        'name' => $assignee->name ?? $assignee->login,
                        'avatar_url' => $assignee->avatar_url ?? '',
                        'type' => $assignee->type ?? 'User',
                    ]
                );
            }
        }

        $issue = Issue::updateOrCreate(
            ['github_id' => $issueData->id],
            [
                'repository_id' => $repository->github_id,
                'opened_by_id' => $userData->id,
                'number' => $issueData->number,
                'title' => $issueData->title,
                'body' => $issueData->body ?? '',
                'state' => $issueData->state,
                'labels' => json_encode($issueData->labels ?? []),
            ]);

        // Sync assignees in the pivot table
        $issue->assignees()->sync($assigneeGithubIds);

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
            self::issue($payload);
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

    public function pullRequest($payload)
    {
        if (!$payload || !isset($payload->pull_request) || !isset($payload->repository)) {
            return false;
        }
        $prData = $payload->pull_request;
        $repositoryData = $payload->repository;

        $userData = $prData->user ?? null;

        // Ensure repository exists first
        $repository = self::update_repo($repositoryData);

        // Create/update the user who opened the pull request
        self::ensureGithubUser($userData);

        PullRequest::updateOrCreate(
            ['github_id' => $prData->id],
            [
                'repository_id' => $repository->github_id,
                'opened_by_id' => $userData->id,
                'number' => $prData->number,
                'title' => $prData->title,
                'body' => $prData->body ?? '',
                'state' => $prData->state,
            ]
        );

        // Sync assignees in the pivot table
        $assigneeGithubIds = [];
        if (!empty($prData->assignees) && is_array($prData->assignees)) {
            foreach ($prData->assignees as $assignee) {
                $assigneeGithubIds[] = $assignee->id;

                // Create/update the assignee in github_users table
                self::ensureGithubUser($assignee);
            }
        }

        $pr = PullRequest::where('github_id', $prData->id)->first();
        if ($pr) {
            $pr->assignees()->sync($assigneeGithubIds);
        }

        if ($payload->action === 'review_requested') {
            // Create/update the requested reviewer in github_users table
            $reviewerData = $payload->requested_reviewer ?? null;
            self::ensureGithubUser($reviewerData);

            RequestedReviewer::updateOrCreate(
                [
                    'pull_request_id' => $prData->id,
                    'user_id' => $reviewerData->id,
                ],
                [
                    'pull_request_id' => $prData->id,
                    'user_id' => $reviewerData->id
                ]
            );
           
        }

        return true;
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
