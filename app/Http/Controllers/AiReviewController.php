<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Services\RepositoryService;
use App\Helpers\ApiHelper;
use OpenAI;

class AiReviewController extends Controller
{
    public function analyze($organizationName, $repositoryName, $number)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $number)
            ->firstOrFail();

        $userContext = request()->input('context', '');

        // Get PR files and build diff text
        $files = PullRequestController::getFiles($organizationName, $repositoryName, $number);
        $diffText = $this->buildDiffText($files);

        if (empty($diffText)) {
            return response()->json([
                'error' => 'No changes found in PR',
                'comments' => [],
            ], 400);
        }

        // Build GPT-4 prompt
        $systemPrompt = <<<SYSTEM
You are an expert code reviewer. Analyze the provided pull request changes for:
- Potential bugs and edge cases
- Performance improvements
- Best practices and code quality
- Security issues
- Clarity and maintainability

Return ONLY a valid JSON object (no markdown, no extra text) with this structure:
{
  "comments": [
    {
      "path": "relative/file/path.js",
      "line": 42,
      "side": "RIGHT",
      "body": "Comment text here"
    }
  ]
}

Notes:
- "line" is the line number in the new version of the file
- "side" must be "RIGHT" for new code
- Only include comments for actual code changes, not context lines
- Each comment should be actionable and specific
- If no issues found, return empty comments array
SYSTEM;

        $userPrompt = "PR Title: {$item->title}\n\n";
        if (!empty($item->body)) {
            $userPrompt .= "PR Description: {$item->body}\n\n";
        }
        if (!empty($userContext)) {
            $userPrompt .= "User Context: {$userContext}\n\n";
        }
        $userPrompt .= "PR Changes:\n\n{$diffText}";

        try {
            $client = OpenAI::client(config('services.openai.api_key'));

            $response = $client->chat()->create([
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt,
                    ],
                ],
                'max_completion_tokens' => 2048,
                'temperature' => 0.7,
            ]);

            $responseText = $response->choices[0]->message->content;

            // Parse JSON response
            $parsed = json_decode($responseText, true);

            if (!$parsed || !isset($parsed['comments'])) {
                // Try to extract JSON if wrapped in markdown
                if (preg_match('/```json\n(.*?)\n```/s', $responseText, $matches)) {
                    $parsed = json_decode($matches[1], true);
                }
            }

            if (!$parsed || !isset($parsed['comments'])) {
                return response()->json([
                    'error' => 'Failed to parse AI response',
                    'comments' => [],
                ], 400);
            }

            // Filter and validate comments
            $validComments = array_filter(
                $parsed['comments'],
                fn($comment) => isset($comment['path']) && isset($comment['line']) && isset($comment['body'])
            );

            return response()->json([
                'success' => true,
                'comments' => array_values($validComments),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'AI analysis failed: ' . $e->getMessage(),
                'comments' => [],
            ], 500);
        }
    }

    public function postComments($organizationName, $repositoryName, $number)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $number)
            ->firstOrFail();

        $comments = request()->input('comments', []);

        if (empty($comments)) {
            return response()->json([
                'success' => true,
                'postedCount' => 0,
            ]);
        }

        $commitSha = $item->getLatestCommitSha();
        $successCount = 0;
        $failedComments = [];

        foreach ($comments as $comment) {
            $payload = [
                'body' => $comment['body'],
                'commit_id' => $commitSha,
                'path' => $comment['path'],
                'line' => $comment['line'],
                'side' => $comment['side'] ?? 'RIGHT',
            ];

            $result = ApiHelper::githubApi(
                "/repos/{$organizationName}/{$repositoryName}/pulls/{$number}/comments",
                'POST',
                $payload
            );

            if ($result) {
                $successCount++;
            } else {
                $failedComments[] = [
                    'path' => $comment['path'],
                    'line' => $comment['line'],
                ];
            }
        }

        return response()->json([
            'success' => true,
            'postedCount' => $successCount,
            'failedCount' => count($failedComments),
            'failedComments' => $failedComments,
        ]);
    }

    /**
     * Build diff text from PR files for AI analysis
     */
    private function buildDiffText(array $files): string
    {
        $parts = [];

        foreach ($files as $file) {
            $parts[] = "File: {$file['filename']} ({$file['status']})";
            $parts[] = "Changes: +{$file['additions']} -{$file['deletions']}";

            if (empty($file['changes'])) {
                $parts[] = "(No diff hunks)";
                $parts[] = "";
                continue;
            }

            foreach ($file['changes'] as $hunk) {
                $parts[] = "@@ -{$hunk['old']['start']} +{$hunk['new']['start']} @@";

                // Add rows with context
                foreach ($hunk['rows'] as $row) {
                    $left = $row['left'];
                    $right = $row['right'];

                    if ($left['type'] === 'normal' || $right['type'] === 'normal') {
                        // Show context with line number
                        $lineNum = $right['number'] ?? $left['number'];
                        $content = $right['content'] ?? $left['content'];
                        $parts[] = " {$lineNum}: {$content}";
                    } elseif ($left['type'] === 'del') {
                        $parts[] = "-{$left['number']}: {$left['content']}";
                    } elseif ($right['type'] === 'add') {
                        $parts[] = "+{$right['number']}: {$right['content']}";
                    }
                }

                $parts[] = "";
            }
        }

        return implode("\n", $parts);
    }
}
