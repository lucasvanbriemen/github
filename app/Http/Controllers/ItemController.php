<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Services\RepositoryService;
use App\Models\Issue;
use App\Models\Commit;
use App\Helpers\ApiHelper;
use App\GithubConfig;
use GrahamCampbell\GitHub\Facades\GitHub;

class ItemController extends Controller
{
    public function index($organizationName, $repositoryName, $type)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $state = request()->query('state', 'open');
        $assignee = request()->query('assignee', 'any');
        $search = request()->query('search', '');

        $query = $repository->items($type, $state, $assignee, $search)
            ->select(['id', 'title', 'state', 'labels', 'created_at', 'opened_by_id', 'number', 'type'])
            ->with([
                'openedBy:id,display_name,avatar_url',
                'assignees:id,name,avatar_url',
            ]);

        $page = request()->query('page', 1);
        $items = $query->paginate(30, ['*'], 'page', $page);

        return response()->json($items);
    }

    public function getLinkedItems($organizationName, $repositoryName, $number)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $number)
            ->firstOrFail();

        $type = "issue";
        if ($item->type !== 'issue') {
            $type = "pullRequest";
        }

        $query = '
            query ($org: String!, $repo: String!, $number: Int!) {
            repository(owner: $org, name: $repo) {
                ' . $type . '(number: $number) {
                timelineItems(
                    first: 100
                    itemTypes: [CONNECTED_EVENT, CROSS_REFERENCED_EVENT, REFERENCED_EVENT]
                ) {
                    nodes {
                    __typename

                    ... on ConnectedEvent {
                        subject {
                        __typename
                        ... on Issue { number title url }
                        ... on PullRequest { number title url }
                        }
                    }

                    ... on CrossReferencedEvent {
                        source {
                        __typename
                        ... on Issue { number title url }
                        ... on PullRequest { number title url }
                        }
                    }

                    ... on ReferencedEvent {
                        subject {
                        __typename
                        ... on Issue { number title url }
                        ... on PullRequest { number title url }
                        }
                    }
                    }
                }
                }
            }
        }';

        $variables = [
            'org' => $organizationName,
            'repo' => $repositoryName,
            'number' => (int) $number,
        ];

        $response = ApiHelper::githubGraphql($query, $variables);

        $ids = [];
        foreach ($response->data->repository->{$type}->timelineItems->nodes as $node) {
            if (isset($node->subject)) {
                $ids[] = $node->subject->number;
            } elseif (isset($node->source)) {
                $ids[] = $node->source->number;
            }
        }

        $items = Item::where('repository_id', $repository->id)->whereIn('number', $ids)->select(['id', 'title', 'state', 'number', 'type', 'created_at'])->get();

        foreach ($items as $item) {
            $type = $item->isPullRequest() ? 'pulls' : 'issues';
            $item->url = "#/{$organizationName}/{$repositoryName}/{$type}/{$item->number}";
        }

        return response()->json($items);
    }

    public function create($organizationName, $repositoryName)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $assigneeInput = request()->input('assignee');
        $assignees[] = $assigneeInput;

        $prData = [
            'title' => request()->input('title'),
            'body' => request()->input('body', ''),
            'draft' => true,
        ];

        $response = GitHub::issues()->create($organization->name, $repository->name, $prData);

        GitHub::issues()->update($organization->name, $repository->name, $response['number'], [
            'assignees' => $assignees,
        ]);

        $state = $response['state'] ?? 'open';

        // Persist base fields in items table
        $issue = Issue::updateOrCreate(
            ['id' => $response['id']],
            [
                'repository_id' => $repository->id,
                'number' => $response['number'] ?? null,
                'title' => $response['title'] ?? '',
                'body' => $response['body'] ?? '',
                'state' => $state,
                'labels' => json_encode($response['labels'] ?? []),
                'opened_by_id' => $response['user']['id'] ?? null,
            ]
        );

        // Sync assignees (uses issue_assignees table)
        $assigneeGithubIds = [];
        if (!empty($response['assignees']) && is_array($response['assignees'])) {
            foreach ($response['assignees'] as $assignee) {
                $assigneeGithubIds[] = $assignee['id'];
            }
        } elseif (!empty($response['assignee']) && is_array($response['assignee']) && isset($response['assignee']['id'])) {
            // GitHub may return a single assignee
            $assigneeGithubIds[] = $response['assignee']['id'];
        }

        $issue->assignees()->sync($assigneeGithubIds);

        return response()->json([
            'number' => $response['number'] ?? null,
            'state' => $state,
        ]);
    }

    public static function show($organizationName, $repositoryName, $issueNumber)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $issueNumber)
            ->with([
                'assignees',
                'openedBy',
                'comments'
            ])
            ->firstOrFail();

        foreach ($item->comments as $comment) {
            self::formatComments($comment);
        }

        // If its a PR we also want to load that specific data
        if ($item->isPullRequest()) {
            $item->load([
                'details',
                'requestedReviewers.user'
            ]);

            // Load the latest commit with workflow information
            $latestSha = $item->getLatestCommitSha();
            $item->latest_commit = Commit::where('sha', $latestSha)->with('workflow')->first();
        }

        $type = $item->isPullRequest() ? 'pullRequest' : 'issue';
        $query = "
            query (\$owner: String!, \$name: String!, \$number: Int!) {
                repository(owner: \$owner, name: \$name) {
                    $type(number: \$number) {
                        id
                        projectItems(first: 10) {
                            nodes {
                                id
                                project {
                                    id
                                    title
                                    number
                                }
                                fieldValueByName(name: \"Status\") {
                                    ... on ProjectV2ItemFieldSingleSelectValue {
                                        name
                                    }
                                }
                            }
                        }
                    }
                }
            }
        ";

        $response = ApiHelper::githubGraphql($query, [
            'owner' => $organizationName,
            'name' => $repository->name,
            'number' => (int) $issueNumber,
        ]);

        $item->node_id = $response->data->repository->{$type}->id;

        $projects = [];
        foreach ($response->data->repository->{$type}->projectItems->nodes as $projectItem) {
            $projectData = [
                'id' => $projectItem->project->id,
                'title' => $projectItem->project->title,
                'number' => $projectItem->project->number,
                'itemId' => $projectItem->id,
                'status' => $projectItem->fieldValueByName->name ?? null
            ];

            $projects[] = $projectData;
        }
        $item->projects = $projects;

        return response()->json($item);
    }

    private static function formatComments($comment)
    {
        if ($comment->type === 'review') {
            $comment->details = $comment->reviewDetails;
        }

        if ($comment->type === 'code') {
            $comment->details = $comment->commentDetails;
        }

        if ($comment->type === 'issue') {
            return;
        }

        $comment->child_comments = $comment->details->childComments ?? [];

        self::formatChildComments($comment);

        unset($comment->reviewDetails);
        unset($comment->commentDetails);
        unset($comment->user_id);
        unset($comment->issue_id);

        if ($comment->details) {
            unset($comment->details->childComments);
            unset($comment->details->base_comment_id);
            unset($comment->details->created_at);
            unset($comment->details->updated_at);
        }
    }

    private static function formatChildComments($parentComment)
    {
        $childComments = $parentComment->child_comments ?? $parentComment->childComments ?? [];

        if (!$childComments) {
            return;
        }

        foreach ($childComments as $childComment) {
            // Child comments get author and body from baseComment
            if ($childComment->baseComment) {
                unset($childComment->author);
                unset($childComment->type);

                $childComment->author = $childComment->baseComment->author;
                $childComment->type = $childComment->baseComment->type;
                $childComment->body = $childComment->baseComment->body ?? '';
                $childComment->resolved = $childComment->baseComment->resolved;

                unset($childComment->baseComment);
            }

            $childComment->id = $childComment->base_comment_id;

            unset($childComment->base_comment_id);
            unset($childComment->details);
            unset($childComment->reviewDetails);
            unset($childComment->commentDetails);
            unset($childComment->details);

            // Recursively format grandchild comments
            self::formatChildComments($childComment);
        }
    }

    public function update($organizationName, $repositoryName, $number)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $number)
            ->firstOrFail();

        $payload = [];
        foreach (request()->all() as $key => $value) {
            if (!in_array($key, ['state'])) {
                continue;
            }

            $payload[$key] = $value;
        }

        GitHub::issues()->update($organizationName, $repositoryName, $number, $payload);

        return response()->json($item);
    }

    public function updateLabels($organizationName, $repositoryName, $number)
    {
        $labels = request()->input('labels');

        GitHub::issues()->labels()->replace(
            $organizationName,
            $repositoryName,
            $number,
            $labels
        );

        return response()->json(['success' => true]);
    }

    public function searchLinkableItems($organizationName, $repositoryName, $number)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $number)
            ->firstOrFail();

        // Search for open items of the opposite type
        $search = request()->query('search', '');
        $oppositeType = $item->isPullRequest() ? 'issue' : 'pull_request';

        $query = Item::where('repository_id', $repository->id)
            ->where('type', $oppositeType)
            ->where('state', 'open');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%$search%")
                  ->orWhere('title', 'like', "%$search%");
            });
        }

        $items = $query->select(['id', 'number', 'title', 'type', 'state'])
            ->orderByDesc('number')
            ->limit(20)
            ->get();

        $result = $items->map(function ($item) {
            return [
                'value' => $item->number,
                'label' => "#{$item->number} - {$item->title}"
            ];
        });

        return response()->json($result);
    }

    public function createLink($organizationName, $repositoryName, $sourceNumber)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $sourceItem = Item::where('repository_id', $repository->id)
            ->where('number', $sourceNumber)
            ->firstOrFail();

        $targetNumber = request()->input('target_number');
        $linkType = request()->input('link_type', 'blocks'); // 'blocks', 'blocked_by'

        $targetItem = Item::where('repository_id', $repository->id)
            ->where('number', $targetNumber)
            ->firstOrFail();

        // Determine which item is the source and which is the target for the GraphQL mutation
        // For 'blocks' relationship: sourceItem blocks targetItem
        if ($linkType === 'blocks') {
            $blockingItemNumber = $sourceNumber;
            $blockedItemNumber = $targetNumber;
        } else {
            $blockingItemNumber = $targetNumber;
            $blockedItemNumber = $sourceNumber;
        }

        $sourceType = $sourceItem->isPullRequest() ? 'PR' : 'Issue';
        $targetType = $targetItem->isPullRequest() ? 'PR' : 'Issue';

        try {
            // If source is a PR and target is an issue, update PR description to add "Closes #X"
            // This will show in the Development section on GitHub
            if ($sourceItem->isPullRequest() && !$targetItem->isPullRequest()) {
                // Get current PR data
                $prData = GitHub::pullRequest()->show(
                    $organizationName,
                    $repository->name,
                    $sourceNumber
                );

                // Add "Closes #targetNumber" to the description if not already there
                $description = $prData['body'] ?? '';
                $closesKeyword = "Closes #$targetNumber";

                if (strpos($description, $closesKeyword) === false) {
                    $description .= "\n\n$closesKeyword";
                }

                // Update the PR with the new description
                GitHub::pullRequest()->update(
                    $organizationName,
                    $repository->name,
                    $sourceNumber,
                    ['body' => trim($description)]
                );
            } else if (!$sourceItem->isPullRequest() && $targetItem->isPullRequest()) {
                // If source is an issue and target is a PR, add "Closes #sourceNumber" to PR description
                $prData = GitHub::pullRequest()->show(
                    $organizationName,
                    $repository->name,
                    $targetNumber
                );

                $description = $prData['body'] ?? '';
                $closesKeyword = "Closes #$sourceNumber";

                if (strpos($description, $closesKeyword) === false) {
                    $description .= "\n\n$closesKeyword";
                }

                GitHub::pullRequest()->update(
                    $organizationName,
                    $repository->name,
                    $targetNumber,
                    ['body' => trim($description)]
                );
            } else {
                // For issue-to-issue links, use REST API dependency endpoint
                $route = "/repos/$organizationName/{$repository->name}/issues/$targetNumber/dependencies/blocked_by";

                $response = ApiHelper::githubApi($route, 'POST', [
                    'issue_id' => (int)$sourceItem->id
                ]);

                if ($response === null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to create link via GitHub API',
                        'debug' => [
                            'http_code' => ApiHelper::$lastHttpCode,
                            'response' => ApiHelper::$lastResponse
                        ]
                    ], 400);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully linked $sourceType #$sourceNumber to $targetType #$targetNumber. This will now appear in the Development section."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create link: ' . $e->getMessage()
            ], 400);
        }
    }

    public function removeLink($organizationName, $repositoryName, $sourceNumber)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $sourceItem = Item::where('repository_id', $repository->id)
            ->where('number', $sourceNumber)
            ->firstOrFail();

        $targetNumber = request()->input('target_number');

        $targetItem = Item::where('repository_id', $repository->id)
            ->where('number', $targetNumber)
            ->firstOrFail();

        try {
            // If source is a PR, remove the "Closes" keyword from PR description
            if ($sourceItem->isPullRequest()) {
                $prData = GitHub::pullRequest()->show(
                    $organizationName,
                    $repository->name,
                    $sourceNumber
                );

                $description = $prData['body'] ?? '';
                $keywords = ['Closes', 'Fixes', 'Resolves'];

                foreach ($keywords as $keyword) {
                    $pattern = "/\n*$keyword\s+#$targetNumber/i";
                    $description = preg_replace($pattern, '', $description);
                }

                GitHub::pullRequest()->update(
                    $organizationName,
                    $repository->name,
                    $sourceNumber,
                    ['body' => trim($description)]
                );
            } else if ($targetItem->isPullRequest()) {
                // If target is a PR, remove the "Closes" keyword from its description
                $prData = GitHub::pullRequest()->show(
                    $organizationName,
                    $repository->name,
                    $targetNumber
                );

                $description = $prData['body'] ?? '';
                $keywords = ['Closes', 'Fixes', 'Resolves'];

                foreach ($keywords as $keyword) {
                    $pattern = "/\n*$keyword\s+#$sourceNumber/i";
                    $description = preg_replace($pattern, '', $description);
                }

                GitHub::pullRequest()->update(
                    $organizationName,
                    $repository->name,
                    $targetNumber,
                    ['body' => trim($description)]
                );
            } else {
                // For issue-to-issue links, use REST API to remove dependency
                $route = "/repos/$organizationName/{$repository->name}/issues/$targetNumber/dependencies/blocked_by/{$sourceItem->id}";

                $response = ApiHelper::githubApi($route, 'DELETE');

                if ($response === null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to remove link via GitHub API'
                    ], 400);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully removed link between #$sourceNumber and #$targetNumber"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove link: ' . $e->getMessage()
            ], 400);
        }
    }
}
