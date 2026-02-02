<?php

declare(strict_types=1);

/*
 * Notification Auto-Resolution Configuration
 *
 * Define triggers that will automatically resolve notifications
 * based on GitHub webhook events and state changes.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Auto-Resolution
    |--------------------------------------------------------------------------
    |
    | Toggle auto-resolution of notifications on/off globally.
    | Individual notification types can also be disabled below.
    |
    */

    'enabled' => env('NOTIFICATIONS_AUTO_RESOLVE', true),

    /*
    |--------------------------------------------------------------------------
    | Notification Type Resolution Rules
    |--------------------------------------------------------------------------
    |
    | Define which triggers will auto-resolve each notification type.
    | Each notification type can have multiple triggers.
    |
    */

    'types' => [

        'item_assigned' => [
            'enabled' => true,
            'description' => 'Auto-resolve when issue/PR is closed, merged, or unassigned',
            'triggers' => [
                'item_closed' => [
                    'enabled' => true,
                    'description' => 'Item (issue/PR) is closed',
                    'event' => 'issues|pull_request',
                    'action' => 'closed',
                    'condition' => 'state === "closed"',
                ],
                'item_merged' => [
                    'enabled' => true,
                    'description' => 'Pull request is merged',
                    'event' => 'pull_request',
                    'action' => 'closed',
                    'condition' => 'merged_at !== null',
                ],
                'unassigned' => [
                    'enabled' => true,
                    'description' => 'Item is unassigned from user',
                    'event' => 'issues|pull_request',
                    'action' => 'unassigned',
                    'condition' => 'user_id not in assignees',
                ],
            ],
        ],

        'item_comment' => [
            'enabled' => true,
            'description' => 'Auto-resolve when item is closed/merged or you comment',
            'triggers' => [
                'item_closed' => [
                    'enabled' => true,
                    'description' => 'Item (issue/PR) is closed',
                    'event' => 'issues|pull_request',
                    'action' => 'closed',
                    'condition' => 'state === "closed"',
                ],
                'item_merged' => [
                    'enabled' => true,
                    'description' => 'Pull request is merged',
                    'event' => 'pull_request',
                    'action' => 'closed',
                    'condition' => 'merged_at !== null',
                ],
                'user_commented' => [
                    'enabled' => true,
                    'description' => 'You add a comment to the item',
                    'event' => 'issue_comment',
                    'action' => 'created|edited',
                    'condition' => 'comment.user_id === configured_user_id',
                ],
            ],
        ],

        'review_requested' => [
            'enabled' => true,
            'description' => 'Auto-resolve when you submit a review or PR is merged',
            'triggers' => [
                'review_submitted' => [
                    'enabled' => true,
                    'description' => 'You submit a review (approval, changes, or comment)',
                    'event' => 'pull_request_review',
                    'action' => 'submitted',
                    'condition' => 'review.user_id === configured_user_id',
                ],
                'pr_merged' => [
                    'enabled' => true,
                    'description' => 'Pull request is merged',
                    'event' => 'pull_request',
                    'action' => 'closed',
                    'condition' => 'merged_at !== null',
                ],
            ],
        ],

        'pr_review' => [
            'enabled' => true,
            'description' => 'Auto-resolve when PR is merged or review is dismissed',
            'triggers' => [
                'pr_merged' => [
                    'enabled' => true,
                    'description' => 'Pull request is merged',
                    'event' => 'pull_request',
                    'action' => 'closed',
                    'condition' => 'merged_at !== null',
                ],
                'review_dismissed' => [
                    'enabled' => true,
                    'description' => 'Review is dismissed by PR author',
                    'event' => 'pull_request_review',
                    'action' => 'dismissed',
                    'condition' => 'review_id matches notification.related_id',
                ],
            ],
        ],

        'workflow_failed' => [
            'enabled' => true,
            'description' => 'Auto-resolve when new commit is pushed (workflow re-runs)',
            'triggers' => [
                'new_commit_pushed' => [
                    'enabled' => true,
                    'description' => 'New commit is pushed to the PR',
                    'event' => 'push|pull_request',
                    'action' => 'synchronize|push',
                    'condition' => 'commit is on same PR as notification',
                ],
            ],
        ],

        'comment_mention' => [
            'enabled' => true,
            'description' => 'Auto-resolve when item is closed/merged or you comment',
            'triggers' => [
                'item_closed' => [
                    'enabled' => true,
                    'description' => 'Item (issue/PR) is closed',
                    'event' => 'issues|pull_request',
                    'action' => 'closed',
                    'condition' => 'state === "closed"',
                ],
                'item_merged' => [
                    'enabled' => true,
                    'description' => 'Pull request is merged',
                    'event' => 'pull_request',
                    'action' => 'closed',
                    'condition' => 'merged_at !== null',
                ],
                'user_commented' => [
                    'enabled' => true,
                    'description' => 'You reply in the comment thread',
                    'event' => 'issue_comment|pull_request_review_comment',
                    'action' => 'created|edited',
                    'condition' => 'comment.user_id === configured_user_id',
                ],
            ],
        ],

    ],

];
