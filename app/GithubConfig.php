<?php

namespace App;

class GithubConfig
{
    public const USERID = 117530797;
    public const USERNAME = "lukaas-007";
    public const USER_EMAIL = "contact@lucasvanbriemen.nl";

    // Run php artisan items:recalculate-scores when changing config
    public const IMPORTANCE_SCORING = [
        'filters' => [
            'assigned_to_user' => true,
            'excluded_labels' => ['waiting', 'needs-uat'],
        ],

        'category_weights' => [
            'milestone_urgency' => 30,      // Deadlines matter most
            'review_status' => 50,          // Actionable items
            'unresolved_comments' => 15,    // Feedback pending
            'project_board_status' => 25,   // Work state context
            'hotfix_friday' => 10,          // Time-based boost
        ],

        'milestone_proximity' => [
            'overdue' => [
                'normalized_score' => 100,
                'escalation_per_day' => 5,  // Capped at 100 total
            ],

            'ranges' => [
                ['min_days' => 0, 'max_days' => 2, 'normalized_score' => 100],
                ['min_days' => 3, 'max_days' => 6, 'normalized_score' => 80],
                ['min_days' => 7, 'max_days' => 14, 'normalized_score' => 50],
                ['min_days' => 15, 'max_days' => 30, 'normalized_score' => 25],
            ],
        ],

        'project_board_status' => [
            'in_progress_keywords' => ['in progress', 'review required', 'UAT (testing done, action for dev)'],
            'normalized_score' => 80,  // Items actively being worked on
        ],

        'hotfix_friday' => [
            'day' => 5, // 0=Sunday, 5=Friday
            'label' => 'hotfix',
            'normalized_score' => 100,  // Hotfix items get max priority on Friday
        ],

        'review_status' => [
            'pending_review_normalized' => -20,
            'changes_requested_normalized' => 100,
            'approved_normalized' => 60,
        ],

        'unresolved_comments' => [
            'max_score_at_count' => 10,  // 10+ comments = 100%
            'critical_reviewer' => 'dewiWG',
            'critical_count_multiplier' => 3,
        ],

        'without_milestone' => [
            'normalized_score' => 10,
        ],
    ];

    public const ORG_RULES = [
        'webinargeek' => [
            'custom_buttons' => [
                'fbe' => 'Deploy stack',
                'needs-uat' => 'Request UAT',
            ],
        ],

        'lucasvanbriemen' => [
            'custom_buttons' => [
                'bug' => 'Start bugfix',
            ],
        ],
    ];
}
