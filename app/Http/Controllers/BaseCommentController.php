<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\PullRequestReview;
use App\Models\BaseComment;
use App\Models\PullRequestComment;
use App\Services\RepositoryService;
use App\Helpers\ApiHelper;
use GrahamCampbell\GitHub\Facades\GitHub;

class BaseCommentController extends Controller
{
    public static function updateItem($organizationName, $repositoryName, $issueNumber, $comment_id)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $issueNumber)
            ->firstOrFail();

        $comment = BaseComment::where('id', $comment_id)
            ->firstOrFail();

        $data = request()->validate([
            'resolved' => 'required|boolean',
        ]);

        $comment->resolved = $data['resolved'];
        $comment->save();

        return response()->json(['success' => true, 'comment' => $comment]);
    }

    public function createItemComment($organizationName, $repositoryName, $issueNumber)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $issueNumber)
            ->firstOrFail();

        $data = request()->validate([
            'body' => 'required|string',
        ]);

        // We need to create the comment on GH first
        $comment = GitHub::issues()
            ->comments()
            ->create(
                $organization->name,
                $repository->name,
                $item->number,
                [
                'body' => $data['body'],
                ]
            );

        $localComment = BaseComment::updateOrCreate(
            ['comment_id' => $comment['id']],
            [
                'issue_id' => $item->id,
                'user_id' => $comment['user']['id'],
                'body' => $comment['body'],
                'created_at' => $comment['created_at'],
                'updated_at' => $comment['updated_at'],
                'type' => 'issue',
                'resolved' => false,
            ]
        );

        $localComment->load(['author']);

        return response()->json($localComment);
    }

    public function createPRComment($organizationName, $repositoryName, $pullRequestNumber)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $pullRequestNumber)
            ->firstOrFail();

        $commitSha = $item->getLatestCommitSha();
        $payload = [
            'body'      => request()->input('body'),
            'commit_id' => $commitSha,
            'path'      => request()->input('path'),
            'line'      => request()->input('line'),
            'side'      => request()->input('side'),
        ];

        $response = ApiHelper::githubApi("/repos/{$organizationName}/{$repositoryName}/pulls/{$pullRequestNumber}/comments", 'POST', $payload);

        return $response;
    }
}
