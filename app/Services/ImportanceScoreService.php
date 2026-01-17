<?php

namespace App\Services;

use App\GithubConfig;
use App\Models\Item;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ImportanceScoreService
{
    /**
     * Calculate the importance score for an item using weighted normalized scores
     * Each category returns 0-100, then weighted and summed for final score
     */

    public static function calculateScore(Item $item): int
    {
        $config = GithubConfig::IMPORTANCE_SCORING;

        // Hard filter: item must be assigned to current user
        if (!$item->isCurrentlyAssignedToUser()) {
            return -999; // Return very low score for non-assigned items
        }

        // Hard filter: exclude items with any excluded labels
        $excludedLabels = $config['filters']['excluded_labels'] ?? [];
        foreach ($excludedLabels as $excludedLabel) {
            if (self::hasLabel($item, $excludedLabel)) {
                return -999;
            }
        }

        $finalScore = 0;
        foreach ($config['category_weights'] as $category => $weight) {
            $method = 'get' . Str::studly($category) . 'Score';

            if (!method_exists(self::class, $method)) {
                continue;
            }

            $normalizedScore = self::$method($item);
            $finalScore += ($normalizedScore * $weight) / 100;
        }

        return (int) round($finalScore);
    }

    /**
     * Get milestone urgency score (0-100)
     * Handles overdue milestones with escalation
     */
    public static function getMilestoneUrgencyScore(Item $item): int
    {
        $config = GithubConfig::IMPORTANCE_SCORING['milestone_proximity'];

        if (!$item->milestone || !$item->milestone->due_on) {
            return GithubConfig::IMPORTANCE_SCORING['without_milestone']['normalized_score'];
        }

        $dueDate = Carbon::parse($item->milestone->due_on);
        $now = Carbon::now();
        $daysUntilDue = $now->diffInDays($dueDate, false); // Can be negative

        // Handle overdue
        if ($daysUntilDue < 0) {
            $daysOverdue = abs($daysUntilDue);
            $baseScore = $config['overdue']['normalized_score'];
            $escalation = min(
                $daysOverdue * $config['overdue']['escalation_per_day'],
                100 - $baseScore
            );
            return min($baseScore + $escalation, 100);
        }

        // Find matching range
        foreach ($config['ranges'] as $range) {
            if ($daysUntilDue >= $range['min_days'] && $daysUntilDue <= $range['max_days']) {
                return $range['normalized_score'];
            }
        }

        return 0;
    }

    /**
     * Get project board status score (0-100)
     */
    public static function getProjectBoardStatusScore(Item $item): int
    {
        $config = GithubConfig::IMPORTANCE_SCORING['project_board_status'];

        $status = self::getProjectStatusViaGraphQL($item);

        if (!$status) {
            return 0;
        }

        $isInProgress = collect($config['in_progress_keywords'])->some(fn($keyword) => str_contains(strtolower($status), $keyword));

        return $isInProgress ? $config['normalized_score'] : 0;
    }

    /**
     * Batch fetch project statuses from GitHub GraphQL API for multiple items
     */
    public static function getProjectStatusesForItems(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        // Group items by (organization, repository)
        $groupedByRepo = [];
        foreach ($items as $item) {
            if (!$item->repository || !$item->repository->organization) {
                continue;
            }

            $repoKey = $item->repository->organization->name . '/' . $item->repository->name;
            if (!isset($groupedByRepo[$repoKey])) {
                $groupedByRepo[$repoKey] = [
                    'org' => $item->repository->organization->name,
                    'repo' => $item->repository->name,
                    'items' => [],
                ];
            }
            $groupedByRepo[$repoKey]['items'][] = $item;
        }

        $statuses = [];

        // Query each repository's items
        foreach ($groupedByRepo as $repoData) {
            try {
                $org = $repoData['org'];
                $repo = $repoData['repo'];
                $itemsInRepo = $repoData['items'];

                // Build a query for up to 10 items per repo
                $queryParts = [];
                foreach (array_slice($itemsInRepo, 0, 10) as $index => $item) {
                    $alias = "item{$index}";
                    $queryParts[] = "$alias: issueOrPullRequest(number: {$item->number}) {
                        ... on Issue {
                            projectItems(first: 10) {
                                nodes {
                                    fieldValueByName(name: \"Status\") {
                                        ... on ProjectV2ItemFieldSingleSelectValue {
                                            name
                                        }
                                    }
                                }
                            }
                        }
                        ... on PullRequest {
                            projectItems(first: 10) {
                                nodes {
                                    fieldValueByName(name: \"Status\") {
                                        ... on ProjectV2ItemFieldSingleSelectValue {
                                            name
                                        }
                                    }
                                }
                            }
                        }
                    }";
                }

                $query = "query (\$owner: String!, \$repo: String!) {
                    repository(owner: \$owner, name: \$repo) {
                        " . implode("\n", $queryParts) . "
                    }
                }";

                $response = \App\Helpers\ApiHelper::githubGraphql($query, [
                    'owner' => $org,
                    'repo' => $repo,
                ]);

                // Extract statuses from response
                foreach (array_slice($itemsInRepo, 0, 10) as $index => $item) {
                    $alias = "item{$index}";
                    $projectItems = $response->data->repository->{$alias}->projectItems->nodes ?? [];

                    if (!empty($projectItems) && isset($projectItems[0]->fieldValueByName->name)) {
                        $statuses[$item->id] = $projectItems[0]->fieldValueByName->name;
                    }
                }
            } catch (\Exception $e) {
                // If GraphQL query fails, skip this repo
                continue;
            }
        }

        return $statuses;
    }

    /**
     * Fetch project status from GitHub GraphQL API for a single item
     */
    private static function getProjectStatusViaGraphQL(Item $item): ?string
    {
        try {
            $item->load(['repository.organization']);

            if (!$item->repository || !$item->repository->organization) {
                return null;
            }

            $organizationName = $item->repository->organization->name;
            $repositoryName = $item->repository->name;
            $itemNumber = $item->number;

            $query = <<<'GRAPHQL'
                query ($org: String!, $repo: String!, $number: Int!) {
                    repository(owner: $org, name: $repo) {
                        issueOrPullRequest(number: $number) {
                            ... on Issue {
                                projectItems(first: 10) {
                                    nodes {
                                        fieldValueByName(name: "Status") {
                                            ... on ProjectV2ItemFieldSingleSelectValue {
                                                name
                                            }
                                        }
                                    }
                                }
                            }
                            ... on PullRequest {
                                projectItems(first: 10) {
                                    nodes {
                                        fieldValueByName(name: "Status") {
                                            ... on ProjectV2ItemFieldSingleSelectValue {
                                                name
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            GRAPHQL;

            $response = \App\Helpers\ApiHelper::githubGraphql($query, [
                'org' => $organizationName,
                'repo' => $repositoryName,
                'number' => $itemNumber,
            ]);

            $data = $response->data->repository->issueOrPullRequest->projectItems->nodes ?? [];

            // Return the first project's status value if available
            if (!empty($data) && isset($data[0]->fieldValueByName->name)) {
                return $data[0]->fieldValueByName->name;
            }

            return null;
        } catch (\Exception $e) {
            // If GraphQL query fails, return null (item won't get project status score)
            return null;
        }
    }

    /**
     * Get hotfix friday score (0-100)
     */
    public static function getHotfixFridayScore(Item $item): int
    {
        $config = GithubConfig::IMPORTANCE_SCORING['hotfix_friday'];

        $today = Carbon::now()->dayOfWeek; // 0=Sunday, 5=Friday

        if ($today !== $config['day']) {
            return 0;
        }

        // Check if item has the hotfix label
        if (self::hasLabel($item, $config['label'])) {
            return $config['normalized_score'];
        }

        return 0;
    }

    /**
     * Get review status score (0-100)
     */
    public static function getReviewStatusScore(Item $item): int
    {
        $config = GithubConfig::IMPORTANCE_SCORING['review_status'];

        if (!$item->isPullRequest()) {
            return 0;
        }

        $reviewStatus = self::getPullRequestReviewStatus($item);

        return match ($reviewStatus) {
            'pending' => $config['pending_review_normalized'],
            'changes_requested' => $config['changes_requested_normalized'],
            'approved' => $config['approved_normalized'],
            default => 0,
        };
    }

    /**
     * Get cached review comments or query if not cached
     */
    private static function getReviewComments(Item $item)
    {
        // Use eager-loaded relation if available
        if ($item->relationLoaded('comments')) {
            return $item->comments->filter(fn($c) => $c->type === 'review');
        }

        // Fallback to query
        return \App\Models\BaseComment::where('issue_id', $item->id)
            ->where('type', 'review')
            ->get();
    }

    /**
     * Get cached code comments or query if not cached
     */
    private static function getCodeComments(Item $item)
    {
        // Use eager-loaded relation if available
        if ($item->relationLoaded('comments')) {
            return $item->comments->filter(fn($c) => $c->type === 'code' && (!$c->resolved || is_null($c->resolved)));
        }

        // Fallback to query
        return \App\Models\BaseComment::where('issue_id', $item->id)
            ->where('type', 'code')
            ->where(function ($q) {
                $q->where('resolved', false)
                  ->orWhereNull('resolved');
            })
            ->get();
    }

    /**
     * Get unresolved comments score (0-100)
     * Normalizes comment count based on max_score_at_count
     */
    public static function getUnresolvedCommentsScore(Item $item): int
    {
        $config = GithubConfig::IMPORTANCE_SCORING['unresolved_comments'];

        // Get unresolved code comments (use cached or query)
        $unresolvedComments = self::getCodeComments($item)->load('author');

        if ($unresolvedComments->isEmpty()) {
            return 0;
        }

        $count = 0;
        foreach ($unresolvedComments as $comment) {
            $authorName = $comment->author?->name ?? '';

            if ($authorName === $config['critical_reviewer']) {
                // Critical reviewer comments count as multiplier
                $count += $config['critical_count_multiplier'];
            } else {
                // Normal unresolved comments
                $count += 1;
            }
        }

        // Normalize: at max_score_at_count, return 100
        $maxCount = $config['max_score_at_count'];
        $normalizedScore = min(($count / $maxCount) * 100, 100);

        return (int) round($normalizedScore);
    }

    /**
     * Determine the review status of a pull request
     */
    public static function getPullRequestReviewStatus(Item $item): string
    {
        if (!$item->isPullRequest()) {
            return 'none';
        }

        // Use cached or query reviews
        $reviews = self::getReviewComments($item)
            ->mapWithKeys(function($review) {
                return [$review->id => $review->reviewDetails];
            })
            ->filter();

        if ($reviews->isEmpty()) {
            return 'pending';
        }

        // Check if any review has changes requested
        $hasChangesRequested = $reviews->contains(fn($review) => $review->state === 'CHANGES_REQUESTED');
        if ($hasChangesRequested) {
            return 'changes_requested';
        }

        // Check if any review has comments
        $hasComments = $reviews->contains(fn($review) => $review->state === 'COMMENTED');
        if ($hasComments) {
            return 'changes_requested'; // Treat comments as actionable
        }

        // Check if all reviews are approved
        $allApproved = $reviews->every(fn($review) => $review->state === 'APPROVED');
        if ($allApproved) {
            return 'approved';
        }

        return 'pending';
    }

    /**
     * Check if an item has a specific label
     */
    private static function hasLabel(Item $item, string $labelName): bool
    {
        if (!is_array($item->labels)) {
            return false;
        }

        return in_array($labelName, $item->labels);
    }

    /**
     * Update the importance score for an item and save it
     */
    public static function updateItemScore(Item $item): void
    {
        $item->importance_score = self::calculateScore($item);
        $item->save();
    }

    /**
     * Recalculate and update scores for multiple items
     */
    public static function updateMultipleItemScores(array $items): void
    {
        foreach ($items as $item) {
            self::updateItemScore($item);
        }
    }
}
