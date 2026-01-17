<?php

namespace App\Services;

use App\GithubConfig;
use App\Models\Item;
use Carbon\Carbon;

class ImportanceScoreService
{
    /**
     * Calculate the importance score for an item
     * Score is based on rules defined in GithubConfig::IMPORTANCE_SCORING
     */
    public static function calculateScore(Item $item): int
    {
        $score = 0;

        // Hard filter: item must be assigned to current user
        if (!$item->isCurrentlyAssignedToUser()) {
            return -999; // Return very low score for non-assigned items
        }

        // Check Project Board Status
        $score += self::getProjectStatusPoints($item);

        // Check Hotfix Friday
        $score += self::getHotfixFridayPoints($item);

        // Check Milestone Proximity
        $score += self::getMilestoneProximityPoints($item);

        // Check Review Status
        $score += self::getReviewStatusPoints($item);

        // Default points for items without milestone
        if (!$item->milestone_id) {
            $score += GithubConfig::IMPORTANCE_SCORING['without_milestone']['default_points'];
        }

        return $score;
    }

    /**
     * Get points based on project board status (In Progress/In Review)
     */
    private static function getProjectStatusPoints(Item $item): int
    {
        $config = GithubConfig::IMPORTANCE_SCORING['project_board_status'];

        if (!$config['enabled']) {
            return 0;
        }

        try {
            $projectItem = \DB::table('project_items')
                ->where('item_id', $item->id)
                ->first();

            if (!$projectItem) {
                return 0;
            }

            $status = \DB::table('project_item_field_values')
                ->where('project_item_id', $projectItem->id)
                ->where('field_name', 'Status')
                ->value('field_value');

            if (!$status) {
                return 0;
            }

            $isInProgress = collect($config['in_progress_keywords'])->some(fn($keyword) => str_contains(strtolower($status), $keyword));

            return $isInProgress ? $config['in_progress_points'] : 0;
        } catch (\Exception $e) {
            // Tables don't exist, return 0
            return 0;
        }
    }

    /**
     * Get points based on Hotfix Friday rules
     */
    private static function getHotfixFridayPoints(Item $item): int
    {
        $config = GithubConfig::IMPORTANCE_SCORING['hotfix_friday'];

        if (!$config['enabled']) {
            return 0;
        }

        $today = Carbon::now()->dayOfWeek; // 0=Sunday, 5=Friday

        if ($today !== $config['day']) {
            return 0;
        }

        // Check if item has the hotfix label
        if (self::hasLabel($item, $config['label'])) {
            return $config['points_when_active'];
        }

        return 0;
    }

    /**
     * Get points based on how close the milestone due date is
     */
    private static function getMilestoneProximityPoints(Item $item): int
    {
        $config = GithubConfig::IMPORTANCE_SCORING['milestone_proximity'];

        if (!$config['enabled'] || !$item->milestone) {
            return 0;
        }

        $dueDate = Carbon::parse($item->milestone->due_on);
        $now = Carbon::now();
        $daysUntilDue = $now->diffInDays($dueDate, false); // Can be negative

        // Find the closest matching days threshold
        $points = 0;
        foreach ($config['points_by_days_until_due'] as $days => $pointsValue) {
            if ($daysUntilDue <= $days && $daysUntilDue > 0) {
                $points = $pointsValue;
                break;
            }
        }

        return $points;
    }

    /**
     * Get points based on review status
     */
    private static function getReviewStatusPoints(Item $item): int
    {
        $config = GithubConfig::IMPORTANCE_SCORING['review_status'];

        if (!$config['enabled'] || !$item->isPullRequest()) {
            return 0;
        }

        $reviewStatus = self::getPullRequestReviewStatus($item);

        return match ($reviewStatus) {
            'pending' => $config['pending_review_points'],
            'changes_requested' => $config['changes_requested_points'],
            'approved' => $config['all_approved_points'],
            default => 0,
        };
    }

    /**
     * Determine the review status of a pull request
     */
    private static function getPullRequestReviewStatus(Item $item): string
    {
        if (!$item->isPullRequest()) {
            return 'none';
        }

        // Get reviews from base_comments table where type='review'
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
