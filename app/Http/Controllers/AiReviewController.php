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
                'unclearItems' => [],
            ], 400);
        }

        // Limit diff text to reduce token usage
        $maxDiffLength = 4000;
        if (strlen($diffText) > $maxDiffLength) {
            $diffText = substr($diffText, 0, $maxDiffLength) . "\n\n[... diff truncated ...]";
        }

        // Build GPT-4 prompt for identifying unclear code
        $systemPrompt = <<<SYSTEM
Analyze all code changes carefully. Flag anything that seems odd, unusual, or noteworthy:
1. Any logic changes - especially if different from old code or seems risky/wrong
2. Anything unusual or weird - if code seems odd/unconventional, flag it
3. Unclear intentions - variable names, logic that's not self-evident
4. Potential bugs - off-by-one, type issues, missing null checks
5. Vague business logic - code that needs context to understand

Be aggressive: Flag anything that even slightly stands out or seems noteworthy.
Only ignore: pure style/whitespace changes.

First analyze the full diff completely, then return findings.

Return ONLY valid JSON:
{"unclearItems": [{"path": "file.js", "line": 42, "code": "code snippet", "reason": "brief reason"}]}
SYSTEM;

        $userPrompt = "PR: {$item->title}";
        if (!empty($item->body)) {
            $userPrompt .= "\n" . substr($item->body, 0, 500);
        }
        if (!empty($userContext)) {
            $userPrompt .= "\nContext: " . substr($userContext, 0, 300);
        }
        $userPrompt .= "\n\nAnalyze this full diff, then identify odd/unclear sections:\n{$diffText}";

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

            if (!$parsed || !isset($parsed['unclearItems'])) {
                // Try to extract JSON if wrapped in markdown
                if (preg_match('/```json\n(.*?)\n```/s', $responseText, $matches)) {
                    $parsed = json_decode($matches[1], true);
                }
            }

            if (!$parsed || !isset($parsed['unclearItems'])) {
                return response()->json([
                    'error' => 'Failed to parse AI response',
                    'unclearItems' => [],
                ], 400);
            }

            // Filter and validate unclear items
            $validItems = array_filter(
                $parsed['unclearItems'],
                fn($item) => isset($item['path']) && isset($item['line']) && isset($item['code']) && isset($item['reason'])
            );

            return response()->json([
                'success' => true,
                'unclearItems' => array_values($validItems),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'AI analysis failed: ' . $e->getMessage(),
                'unclearItems' => [],
            ], 500);
        }
    }

    public function generateComments($organizationName, $repositoryName, $number)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $number)
            ->firstOrFail();

        $unclearItems = request()->input('unclearItems', []);
        $clarifications = request()->input('clarifications', []);

        if (empty($unclearItems)) {
            return response()->json([
                'error' => 'No unclear items provided',
                'comments' => [],
            ], 400);
        }

        // Build the unclear items with clarifications for the prompt
        $itemsWithClarifications = [];
        foreach ($unclearItems as $index => $item) {
            $clarification = $clarifications[$index] ?? null;
            if ($clarification) {
                $itemsWithClarifications[] = [
                    'path' => $item['path'],
                    'line' => $item['line'],
                    'code' => $item['code'],
                    'reason' => $item['reason'],
                    'clarification' => $clarification,
                ];
            }
        }

        if (empty($itemsWithClarifications)) {
            return response()->json([
                'success' => true,
                'comments' => [],
            ]);
        }

        // Build the list of items for the prompt (limit length to reduce tokens)
        $itemsList = '';
        $charCount = 0;
        $maxChars = 3000;
        foreach ($itemsWithClarifications as $idx => $item) {
            $itemText = "Item " . ($idx + 1) . ": {$item['path']}:{$item['line']}\n";
            $itemText .= "Code: {$item['code']}\n";
            $itemText .= "Clarification: {$item['clarification']}\n\n";

            if ($charCount + strlen($itemText) > $maxChars) {
                break;
            }
            $itemsList .= $itemText;
            $charCount += strlen($itemText);
        }

        // Build GPT-4 prompt for generating comments based on clarifications
        $systemPrompt = <<<SYSTEM
Generate concise inline comments explaining:
- Why the logic changed this way
- What the code does that might be unclear
- Any potential issues and why they're handled this way

Comments should be brief and specific to the code snippet.

Return ONLY valid JSON:
{"comments": [{"path": "file.js", "line": 42, "side": "RIGHT", "body": "brief comment"}]}
SYSTEM;

        $userPrompt = "Generate explanatory comments:\n\n{$itemsList}";

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
                'error' => 'Comment generation failed: ' . $e->getMessage(),
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
