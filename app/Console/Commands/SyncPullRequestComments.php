<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PullRequest;
use App\Models\PullRequestReviewComment;
use App\Models\Repository;
use App\Models\GithubUser;
use App\Helpers\ApiHelper;

class SyncPullRequestComments extends Command
{
    protected $signature = 'github:sync-pr-comments {repository} {pr_number}';
    protected $description = 'Sync review comments for a specific pull request including diff hunks';

    public function handle()
    {
        $repoName = $this->argument('repository');
        $prNumber = $this->argument('pr_number');

        // Parse repository name (owner/repo format)
        $parts = explode('/', $repoName);
        if (count($parts) !== 2) {
            $this->error('Repository must be in owner/repo format');
            return 1;
        }

        [$owner, $repo] = $parts;

        // Fetch PR review comments from GitHub API
        $response = ApiHelper::githubApi("/repos/{$owner}/{$repo}/pulls/{$prNumber}/comments");

        if (!$response) {
            $this->error("Failed to fetch review comments for PR #{$prNumber}");
            return 1;
        }

        // Get the repository and PR from database
        $repository = Repository::where('name', $repo)->first();
        if (!$repository) {
            $this->error("Repository {$repo} not found in database");
            return 1;
        }

        $pullRequest = PullRequest::where('repository_id', $repository->github_id)
            ->where('number', $prNumber)
            ->first();

        if (!$pullRequest) {
            $this->error("Pull request #{$prNumber} not found in database");
            return 1;
        }

        $this->info("Processing review comments for PR #{$prNumber}");

        foreach ($response as $comment) {
            // Ensure user exists
            if (isset($comment->user)) {
                GithubUser::updateOrCreate(
                    ['github_id' => $comment->user->id],
                    [
                        'name' => $comment->user->login,
                        'avatar_url' => $comment->user->avatar_url ?? '',
                    ]
                );
            }

            // Create or update the review comment
            $reviewComment = PullRequestReviewComment::updateOrCreate(
                ['github_id' => $comment->id],
                [
                    'pull_request_github_id' => $pullRequest->github_id,
                    'pull_request_review_github_id' => $comment->pull_request_review_id ?? null,
                    'user_id' => $comment->user->id ?? null,
                    'body' => $comment->body ?? '',
                    'path' => $comment->path ?? null,
                    'diff_hunk' => $comment->diff_hunk ?? null,
                    'commit_id' => $comment->commit_id ?? null,
                    'in_reply_to_id' => $comment->in_reply_to_id ?? null,
                    'original_line' => $comment->original_line ?? null,
                    'line' => $comment->line ?? null,
                    'side' => $comment->side ?? null,
                    'start_line' => $comment->start_line ?? null,
                    'start_side' => $comment->start_side ?? null,
                ]
            );

            $this->info("Synced comment on {$reviewComment->path}");
        }

        // Now fetch conversation comments to check for resolution status
        $conversationResponse = ApiHelper::githubApi("/repos/{$owner}/{$repo}/pulls/{$prNumber}/reviews");

        if ($conversationResponse) {
            foreach ($conversationResponse as $review) {
                if (!isset($review->id)) continue;

                // Check if review has resolved threads
                $reviewCommentsResponse = ApiHelper::githubApi("/repos/{$owner}/{$repo}/pulls/{$prNumber}/reviews/{$review->id}/comments");

                if ($reviewCommentsResponse) {
                    foreach ($reviewCommentsResponse as $reviewComment) {
                        if (isset($reviewComment->id) && isset($reviewComment->resolved) && $reviewComment->resolved) {
                            $comment = PullRequestReviewComment::where('github_id', $reviewComment->id)->first();
                            if ($comment) {
                                $comment->update(['resolved' => true]);
                                $this->info("Marked comment {$reviewComment->id} as resolved");
                            }
                        }
                    }
                }
            }
        }

        $commentCount = $pullRequest->reviewComments()->count();
        $this->info("Successfully synced {$commentCount} review comment(s) for PR #{$prNumber}");

        return 0;
    }
}