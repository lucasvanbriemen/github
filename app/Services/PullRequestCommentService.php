<?php

namespace App\Services;

use App\Models\PullRequest;
use Illuminate\Support\Collection;

class PullRequestCommentService
{
    public function getCommentsForDisplay(PullRequest $pullRequest): Collection
    {
        return collect()
            ->merge($pullRequest->comments)
            ->merge($pullRequest->pullRequestComments)
            ->merge($pullRequest->pullRequestReviews)
            ->sortBy('created_at');
    }

    public function groupCommentsByThread(Collection $comments): Collection
    {
        $threads = collect();
        $replies = collect();

        foreach ($comments as $comment) {
            if ($comment->in_reply_to_id) {
                $replies->push($comment);
            } else {
                $threads->push($comment);
            }
        }

        return $threads->map(function ($thread) use ($replies) {
            $thread->replies = $replies->where('in_reply_to_id', $thread->id);
            return $thread;
        });
    }
}