<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\PullRequestReview;
use App\Models\PullRequestComment;
use App\Services\RepositoryService;

class ItemCommentController extends Controller
{
    public static function updateItem($organizationName, $repositoryName, $issueNumber, $comment_id)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $issueNumber)
            ->firstOrFail();

        $comment = $item->comments()->where('id', $comment_id)->firstOrFail();

        $data = request()->validate([
            'resolved' => 'required|boolean',
        ]);

        $comment->resolved = $data['resolved'];
        $comment->save();

        return response()->json(['success' => true, 'comment' => $comment]);
    }

    public static function updateReview($organizationName, $repositoryName, $issueNumber, $review_id)
    {
        // [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $review = PullRequestReview::where('id', $review_id)
            ->firstOrFail();

        $data = request()->validate([
            'resolved' => 'required|boolean',
        ]);

        $review->resolved = $data['resolved'];
        $review->save();

        return response()->json(['success' => true, 'review' => $review]);
    }

    public static function updateReviewComment($organizationName, $repositoryName, $issueNumber, $comment_id)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $comment = PullRequestComment::where('id', $comment_id)
            ->firstOrFail();

        $data = request()->validate([
            'resolved' => 'required|boolean',
        ]);

        $comment->resolved = $data['resolved'];
        $comment->save();

        return response()->json(['success' => true, 'comment' => $comment]);
    }
}
