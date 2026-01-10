<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\PullRequestReview;
use App\Models\BaseComment;
use App\Models\PullRequestComment;
use App\Services\RepositoryService;
use App\Helpers\ApiHelper;
use GrahamCampbell\GitHub\Facades\GitHub;
use OpenAI;

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

        $inReplyToId = request()->input('in_reply_to_id');

        if ($inReplyToId) {
            $parentComment = BaseComment::find($inReplyToId);

            ApiHelper::githubApi(
                "/repos/{$organizationName}/{$repositoryName}/pulls/{$pullRequestNumber}/comments",
                'POST',
                [
                    'body' => request()->input('body'),
                    'in_reply_to' => $parentComment->comment_id,
                ]
            );

            return response()->json(['success' => true]);
        }

        $commitSha = $item->getLatestCommitSha();
        $payload = [
            'body'      => request()->input('body'),
            'commit_id' => $commitSha,
            'path'      => request()->input('path'),
            'line'      => request()->input('line'),
            'side'      => request()->input('side'),
        ];

        ApiHelper::githubApi("/repos/{$organizationName}/{$repositoryName}/pulls/{$pullRequestNumber}/comments", 'POST', $payload);

        // For simplicity, we won't store the comment locally for now and we let the webhook handle it
        return response()->json(['success' => true]);
    }

    public function improveComment()
    {
        $apiKey = config('services.openai.api_key');
        if (!$apiKey) {
            return response()->json([
                'error' => 'OpenAI API key not configured. Add OPENAI_API_KEY to .env',
            ], 400);
        }

        $data = request()->validate([
            'text' => 'required|string|max:5000',
        ]);

        try {
            $client = OpenAI::client($apiKey);

            $response = $client->chat()->create([
                'model' => 'gpt-5-mini',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Improve this GitHub comment for clarity, grammar, and professionalism. Keep it concise and maintain the original intent. Return ONLY the improved text without any explanation:\n\n{$data['text']}",
                    ],
                ],
                'max_completion_tokens' => 1024,
            ]);

            $improved = $response->choices[0]->message->content;

            return response()->json([
                'original' => $data['text'],
                'improved' => $improved,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to improve comment: ' . $e->getMessage(),
            ], 500);
        }
    }
}
