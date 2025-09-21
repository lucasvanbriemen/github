<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\PullRequest;
use App\Models\PullRequestReview;
use App\Models\PullRequestReviewComment;
use App\Models\PullRequestComment;
use App\Models\Repository;
use App\Models\GithubUser;
use Illuminate\Http\Request;

class IncomingWebhookController extends Controller
{
    public $ISSUE_RELATED = ['issues'];

    public $ISSUE_COMMENT_RELATED = ['issue_comment'];

    public $PR_RELATED = ['pull_request'];

    public $PR_REVIEW_RELATED = ['pull_request_review'];

    public $PR_REVIEW_COMMENT_RELATED = ['pull_request_review_comment'];

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

        if (in_array($eventType, $this->PR_RELATED)) {
            $this->pr($payload);
        }

        if (in_array($eventType, $this->PR_REVIEW_RELATED)) {
            $this->prReview($payload);
        }

        if (in_array($eventType, $this->PR_REVIEW_COMMENT_RELATED)) {
            $this->prReviewComment($payload);
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

        $assigneeIds = [];
        // We have to loop over the assignees, to only store their IDs instead of full objects
        if (!empty($issueData->assignees) && is_array($issueData->assignees)) {
            foreach ($issueData->assignees as $assignee) {
                $assigneeIds[] = $assignee->id;
            }
        }

        Issue::updateOrCreate(
            ['github_id' => $issueData->id],
            [
                'repository_id' => $repository->github_id,
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
        if (!$payload || !isset($payload->comment) || !isset($payload->issue) || !isset($payload->repository)) {
            return false;
        }
        $commentData = $payload->comment;
        $issueData = $payload->issue;
        $repositoryData = $payload->repository;

        $userData = $commentData->user ?? null;

        // Ensure repository exists first
        $repository = self::update_repo($repositoryData);

        // PR general comments also come through issue_comment when the issue is a PR
        if (isset($issueData->pull_request)) {
            // Find the PR by repository + number
            $pr = PullRequest::where('repository_id', $repository->github_id)
                ->where('number', $issueData->number ?? null)
                ->first();

            if ($pr) {
                self::ensureGithubUser($userData);
                PullRequestComment::updateOrCreate(
                    ['github_id' => $commentData->id],
                    [
                        'pull_request_github_id' => $pr->github_id,
                        'user_id' => $userData->id ?? null,
                        'body' => $commentData->body ?? '',
                    ]
                );

                return true;
            }
            // If we cannot find PR, fall through to issue comment handling
        }

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

    public function pr($payload)
    {
        $pr = $payload->pull_request;
        $repositoryData = $payload->repository;

        $repository = self::update_repo($repositoryData);

        $author = self::ensureGithubUser($pr->user ?? null);

        $labels = [];
        if (!empty($pr->labels) && is_array($pr->labels)) {
            foreach ($pr->labels as $label) {
                $labels[] = [
                    'id' => $label->id ?? null,
                    'name' => $label->name ?? '',
                    'color' => $label->color ?? '',
                ];
            }
        }

        $state = $pr->state ?? 'open';
        if (($state === 'closed') && ($pr->merged ?? false)) {
            $state = 'merged';
        }

        PullRequest::updateOrCreate(
            ['github_id' => $pr->id],
            [
                'repository_id' => $repository->github_id,
                'number' => $pr->number,
                'title' => $pr->title ?? '',
                'body' => $pr->body ?? '',
                'state' => $state,
                'draft' => (bool)($pr->draft ?? false),
                'author_id' => $author?->github_id,
                'source_branch' => $pr->head->ref ?? '',
                'target_branch' => $pr->base->ref ?? '',
                'node_id' => $pr->node_id ?? null,
                'labels' => json_encode($labels),
            ]
        );

        // Sync assignees
        $assigneeIds = [];
        if (!empty($pr->assignees) && is_array($pr->assignees)) {
            foreach ($pr->assignees as $assignee) {
                $user = self::ensureGithubUser($assignee);
                if ($user) $assigneeIds[] = $user->github_id;
            }
        }

        // Sync requested reviewers
        $reviewerIds = [];
        if (!empty($pr->requested_reviewers) && is_array($pr->requested_reviewers)) {
            foreach ($pr->requested_reviewers as $reviewer) {
                $user = self::ensureGithubUser($reviewer);
                if ($user) $reviewerIds[] = $user->github_id;
            }
        }

        $model = PullRequest::where('github_id', $pr->id)->first();
        if ($model) {
            if (!empty($assigneeIds)) {
                $model->assignees()->sync($assigneeIds);
            } else {
                $model->assignees()->sync([]);
            }
            if (!empty($reviewerIds)) {
                $model->reviewers()->sync($reviewerIds);
            } else {
                $model->reviewers()->sync([]);
            }
        }

        return true;
    }

    public function prReview($payload)
    {
        $review = $payload->review ?? null;
        $pr = $payload->pull_request ?? null;
        $repositoryData = $payload->repository ?? null;

        if (! $review || ! $pr) return true;

        self::update_repo($repositoryData);
        self::ensureGithubUser($review->user ?? null);

        PullRequestReview::updateOrCreate(
            ['github_id' => $review->id],
            [
                'pull_request_github_id' => $pr->id,
                'user_id' => $review->user->id ?? null,
                'state' => $review->state ?? 'commented',
                'body' => $review->body ?? '',
                'submitted_at' => $review->submitted_at ?? null,
            ]
        );

        return true;
    }

    public function prReviewComment($payload)
    {
        $comment = $payload->comment ?? null;
        $pr = $payload->pull_request ?? null;
        $repositoryData = $payload->repository ?? null;

        if (! $comment || ! $pr) return true;

        self::update_repo($repositoryData);
        self::ensureGithubUser($comment->user ?? null);

        PullRequestReviewComment::updateOrCreate(
            ['github_id' => $comment->id],
            [
                'pull_request_github_id' => $pr->id,
                'pull_request_review_github_id' => $comment->pull_request_review_id ?? null,
                'user_id' => $comment->user->id ?? null,
                'body' => $comment->body ?? '',
                'path' => $comment->path ?? null,
                'diff_hunk' => $comment->diff_hunk ?? null,
                'commit_id' => $comment->commit_id ?? null,
                'in_reply_to_id' => $comment->in_reply_to_id ?? null,
            ]
        );

        return true;
    }
}
