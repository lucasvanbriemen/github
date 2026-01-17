<?php

namespace App;

class GithubConfig
{
    public const USERID = 117530797;
    public const USERNAME = "lukaas-007";
    public const USER_EMAIL = "contact@lucasvanbriemen.nl";

    /**
     * Importance scoring system for items
     * Items assigned to the current user are filtered first, then scored
     * Higher score = higher priority
     */

    // Run php artisan items:recalculate-scores when changing config
    public const IMPORTANCE_SCORING = [
        'filters' => [
            // Hard filter: must be assigned to current user
            'assigned_to_user' => true,
        ],

        'project_board_status' => [
            'enabled' => true,
            // Keywords to match in project status field
            'in_progress_keywords' => ['in progress', 'review required', 'UAT (testing done, action for dev)'],
            'in_progress_points' => 20, // Items actively being worked on
        ],

        'hotfix_friday' => [
            'enabled' => true,
            'day' => 5, // 0=Sunday, 5=Friday
            'label' => 'hotfix',
            'hide_non_hotfix_on_friday' => true,
            'points_when_active' => 100, // Hotfix items get max priority on Friday
        ],

        'milestone_proximity' => [
            'enabled' => true,
            // Points increase as deadline approaches
            'points_by_days_until_due' => [
                1 => 100,  // Due tomorrow: max points
                3 => 80,   // Due in 3 days: high points
                7 => 50,   // Due in 7 days: moderate points
            ],
        ],

        'review_status' => [
            'enabled' => true,
            'pending_review_points' => -40,      // Deprioritize: blocked waiting
            'changes_requested_points' => 60,    // Actionable: can fix now
            'all_approved_points' => 40,         // Ready to merge/deploy
        ],

        'without_milestone' => [
            'default_points' => 10, // Items without milestone still get baseline points
        ],
    ];
}
