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

            // Auto-unresolve parent comment if it's resolved
            if ($parentComment->resolved) {
                $parentComment->resolved = false;
                $parentComment->save();
            }

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
        $client = OpenAI::client(config('services.openai.api_key'));

        $response = $client->chat()->create([
            'model' => 'gpt-5-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => <<<TEXT
                        You are an expert in refining GitHub Markdown to improve clarity, professionalism, structure, and grammatical correctness while strictly preserving the original intent.

                        Return only the improved text. Do not include explanations, commentary, or meta remarks. Do not introduce new examples, content, or assumptions that were not present in the original text.

                        Use Markdown features where appropriate, including:

                        blockquotes, and emphasis

                        Properly formatted links using [link text](url)
                        Properly formatted code blocks with syntax highlighting:

                        ```language
                        // code here
                        ```
                        Ensure the result is concise, precise, and neutral in tone. Never be passive-aggressive or rude.
                    TEXT,
                ],
                [
                    'role' => 'user',
                    'content' => request()->input('text'),
                ],
            ],
            'max_completion_tokens' => 1024,
        ]);

        $improved = $response->choices[0]->message->content;

        return response()->json(['improved' => $improved]);
    }
}
