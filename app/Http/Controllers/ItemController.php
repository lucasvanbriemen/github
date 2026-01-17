<?php

namespace App\Http\Controllers;

use Fuse\Fuse;

use App\Models\Item;
use App\Services\RepositoryService;
use App\Services\ImportanceScoreService;
use App\Models\Issue;
use App\Models\Commit;
use App\Helpers\ApiHelper;
use App\GithubConfig;
use GrahamCampbell\GitHub\Facades\GitHub;
use Carbon\Carbon;

class ItemController extends Controller
{
    public function nextToWorkOn()
    {
        $items = Item::where('importance_score', '>', 0)
            ->where('state', 'open')
            ->orderBy('importance_score', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->with([
                'repository.organization:id,name',
                'openedBy:id,display_name,avatar_url',
                'assignees:id,name,avatar_url',
                'milestone:id,due_on,title',
            ])
            ->get();

        // Add score breakdown and project info to each item
        $items->each(function ($item) {
            $item->score_breakdown = $this->getScoreBreakdown($item);
            $item->project_status = $this->getProjectStatus($item);
        });

        return response()->json($items);
    }

    private function getProjectStatus(Item $item): ?string
    {
        try {
            // Get the project status from the item's project board
            $projectItem = \DB::table('project_items')
                ->where('item_id', $item->id)
                ->first();

            if (!$projectItem) {
                return null;
            }

            // Get the field value for the status
            $status = \DB::table('project_item_field_values')
                ->where('project_item_id', $projectItem->id)
                ->where('field_name', 'Status')
                ->value('field_value');

            return $status;
        } catch (\Exception $e) {
            // Tables don't exist, return null
            return null;
        }
    }

    public function index($organizationName, $repositoryName, $type)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $state = request()->query('state', 'open');
        $assignee = request()->query('assignee', 'any');
        $search = request()->query('search', '');
        $milestone = request()->query('milestone', null);

        $query = $repository->items($type, $state, $assignee, $search, $milestone)
            ->select(['id', 'title', 'state', 'labels', 'created_at', 'opened_by_id', 'number', 'type', 'importance_score', 'milestone_id'])
            ->with([
                'openedBy:id,display_name,avatar_url',
                'assignees:id,name,avatar_url',
                'milestone:id,due_on',
            ]);

        $page = request()->query('page', 1);
        $items = $query->paginate(30, ['*'], 'page', $page);

        // Add score breakdown to each item
        $items->getCollection()->transform(function ($item) {
            $item->score_breakdown = $this->getScoreBreakdown($item);
            return $item;
        });

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

        $keywords = ['Closes', 'Fixes', 'Resolves', 'Close', 'Fix', 'Resolve'];
        foreach ($keywords as $keyword) {
            // Match patterns like "Closes #85" or "closes: #85" or "closes #85,"
            if (preg_match_all("/\b$keyword\s+#(\d+)\b/i", $item->body, $matches)) {
                foreach ($matches[1] as $issueNumber) {
                    $ids[] = (int)$issueNumber;
                }
            }
        }

        $ids = array_unique($ids);
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
                'comments',
                'milestone',
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
            if (!in_array($key, ['state', 'milestone', 'body'])) {
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

    public function updateAssignees($organizationName, $repositoryName, $number)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $number)
            ->firstOrFail();

        $currentAssignees = $item->assignees->pluck('login')->all();
        $updatedAssignees = request()->input('assignees', []);

        $toBeAdded   = array_values(array_diff($updatedAssignees, $currentAssignees));
        $toBeRemoved = array_values(array_diff($currentAssignees, $updatedAssignees));

        GitHub::issues()->assignees()->add($organizationName, $repositoryName, $number, ['assignees' => $toBeAdded]);
        GitHub::issues()->assignees()->remove($organizationName, $repositoryName, $number, ['assignees' => $toBeRemoved]);
    }

    public function searchLinkableItems($organizationName, $repositoryName, $number)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);
        $item = Item::where('repository_id', $repository->id)
            ->where('number', $number)
            ->firstOrFail();

        $search = request()->query('search', '');
        $oppositeType = $item->isPullRequest() ? 'issue' : 'pull_request';

        $items = Item::where('repository_id', $repository->id)
            ->where('type', $oppositeType)
            ->select(['id', 'number', 'title', 'type', 'state'])
            ->get();

        // Use Fuse.js for fuzzy search if search term provided
        if ($search) {
            $fuse = new Fuse($items->toArray(), [
                'keys' => ['number', 'title'],
                'threshold' => 0.3,
                'shouldSort' => true,
            ]);

            $results = $fuse->search($search);
            $items = collect($results)->pluck('item');
        }

        $result = $items->take(100)->map(function ($item) {
            return [
                'value' => $item['number'] ?? $item->number,
                'label' => $item['title'] ?? $item->title,
                'state' => $item['state'] ?? $item->state,
                'type' => $item['type'] ?? $item->type
            ];
        });

        return response()->json($result);
    }

    public function createBulkLinks($organizationName, $repositoryName, $sourceNumber)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $sourceItem = Item::where('repository_id', $repository->id)
            ->where('number', $sourceNumber)
            ->firstOrFail();

        $targetNumbers = request()->input('target_numbers', []);

        foreach ($targetNumbers as $targetNumber) {
            $targetItem = Item::where('repository_id', $repository->id)
                ->where('number', $targetNumber)
                ->firstOrFail();

            if ($sourceItem->isPullRequest() && !$targetItem->isPullRequest()) {
                $itemToUpdate = $sourceItem;
                $itemNumberToLink = $targetNumber;
            } elseif (!$sourceItem->isPullRequest() && $targetItem->isPullRequest()) {
                $itemToUpdate = $targetItem;
                $itemNumberToLink = $sourceNumber;
            } else {
                // Both are issues, skip linking
                continue;
            }

            $description = $itemToUpdate->body;
            $closesKeyword = "Closes #$itemNumberToLink";

            if (strpos($description, $closesKeyword) === false) {
                $description .= "\n\n$closesKeyword";
            }

            GitHub::pullRequest()->update(
                $organizationName,
                $repository->name,
                $itemToUpdate->number,
                ['body' => trim($description)]
            );
        }

        return response()->json(['success' => true]);
    }

    public function removeBulkLinks($organizationName, $repositoryName, $sourceNumber)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $sourceItem = Item::where('repository_id', $repository->id)
            ->where('number', $sourceNumber)
            ->firstOrFail();

        $targetNumbers = request()->input('target_numbers', []);

        $keywords = ['Closes', 'Fixes', 'Resolves'];

        foreach ($targetNumbers as $targetNumber) {
            $targetItem = Item::where('repository_id', $repository->id)
                ->where('number', $targetNumber)
                ->firstOrFail();

            if ($sourceItem->isPullRequest() && !$targetItem->isPullRequest()) {
                $itemToUpdate = $sourceItem;
                $itemNumberToUnlink = $targetNumber;
            } elseif (!$sourceItem->isPullRequest() && $targetItem->isPullRequest()) {
                $itemToUpdate = $targetItem;
                $itemNumberToUnlink = $sourceNumber;
            } else {
                // Both are issues or both are PRs, skip unlinking
                continue;
            }

            $description = $itemToUpdate->body;

            foreach ($keywords as $keyword) {
                $pattern = "/\n*$keyword\s+#$itemNumberToUnlink/i";
                $description = preg_replace($pattern, '', $description);
            }

            GitHub::pullRequest()->update(
                $organizationName,
                $repository->name,
                $itemToUpdate->number,
                ['body' => trim($description)]
            );
        }

        return response()->json(['success' => true]);
    }

    private function getScoreBreakdown(Item $item): array
    {
        $breakdown = [];
        $config = GithubConfig::IMPORTANCE_SCORING;

        // Check if assigned to user
        if (!$item->isCurrentlyAssignedToUser()) {
            $breakdown[] = 'Not assigned to you';
            return $breakdown;
        }

        // Project board status (In Progress/In Review)
        $projectStatus = $this->getProjectStatus($item);
        if ($projectStatus) {
            $config = GithubConfig::IMPORTANCE_SCORING['project_board_status'];
            $isInProgress = collect($config['in_progress_keywords'])->some(fn($keyword) => str_contains(strtolower($projectStatus), $keyword));
            if ($isInProgress) {
                $breakdown[] = "Project status ($projectStatus): " . $config['in_progress_points'] . " pts";
            }
        }

        // Hotfix Friday
        $today = Carbon::now()->dayOfWeek;
        if ($today == $config['hotfix_friday']['day']) {
            $hasHotfixLabel = is_array($item->labels) && in_array($config['hotfix_friday']['label'], $item->labels);
            if ($hasHotfixLabel) {
                $breakdown[] = 'Hotfix Friday: ' . $config['hotfix_friday']['points_when_active'] . ' pts';
            }
        }

        // Milestone proximity
        if ($item->milestone && $item->milestone->due_on) {
            $daysUntilDue = Carbon::now()->diffInDays(Carbon::parse($item->milestone->due_on), false);
            if ($daysUntilDue > 0 && $daysUntilDue <= 7) {
                $points = 0;
                foreach ($config['milestone_proximity']['points_by_days_until_due'] as $days => $pts) {
                    if ($daysUntilDue <= $days) {
                        $points = $pts;
                        break;
                    }
                }
                if ($points > 0) {
                    $breakdown[] = "Due in $daysUntilDue days: $points pts";
                }
            }
        }

        // Review status (for PRs)
        if ($item->isPullRequest()) {
            $reviewStatus = $this->getPullRequestReviewStatus($item);
            if ($reviewStatus === 'pending') {
                $breakdown[] = 'Pending review: ' . $config['review_status']['pending_review_points'] . ' pts';
            } elseif ($reviewStatus === 'changes_requested') {
                $breakdown[] = 'Changes requested: ' . $config['review_status']['changes_requested_points'] . ' pts';
            } elseif ($reviewStatus === 'approved') {
                $breakdown[] = 'All approved: ' . $config['review_status']['all_approved_points'] . ' pts';
            }
        }

        // No milestone baseline
        if (!$item->milestone_id) {
            $breakdown[] = 'No milestone: ' . $config['without_milestone']['default_points'] . ' pts';
        }

        if (empty($breakdown)) {
            $breakdown[] = 'Score: ' . $item->importance_score . ' pts';
        }

        return $breakdown;
    }

    private function getPullRequestReviewStatus(Item $item): string
    {
        if (!$item->isPullRequest()) {
            return 'none';
        }

        $reviews = \App\Models\BaseComment::where('issue_id', $item->id)
            ->where('type', 'review')
            ->get()
            ->mapWithKeys(function($review) {
                return [$review->id => $review->reviewDetails];
            })
            ->filter();

        if ($reviews->isEmpty()) {
            return 'pending';
        }

        $hasChangesRequested = $reviews->contains(fn($review) => $review->state === 'CHANGES_REQUESTED');
        if ($hasChangesRequested) {
            return 'changes_requested';
        }

        $hasComments = $reviews->contains(fn($review) => $review->state === 'COMMENTED');
        if ($hasComments) {
            return 'changes_requested';
        }

        $allApproved = $reviews->every(fn($review) => $review->state === 'APPROVED');
        if ($allApproved) {
            return 'approved';
        }

        return 'pending';
    }
}
