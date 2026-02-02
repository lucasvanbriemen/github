<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Collection;

class NotificationAutoResolver
{
    /**
     * Resolve notifications based on item closed event
     */
    public static function resolveOnItemClosed(
        string $itemType,
        int $itemId,
        string $state
    ): int {
        if ($state !== 'closed' && $state !== 'merged') {
            return 0;
        }

        $resolvableTypes = ['item_assigned', 'item_comment', 'comment_mention'];

        return Notification::whereIn('type', $resolvableTypes)
            ->where('related_id', $itemId)
            ->where('completed', false)
            ->update(['completed' => true]);
    }

    /**
     * Resolve review_requested notifications when user submits a review
     */
    public static function resolveOnReviewSubmitted(
        int $prId,
        int $reviewerId
    ): int {
        $configured_user_id = config('app.GithubConfig')::USERID;

        if ($reviewerId !== $configured_user_id) {
            return 0;
        }

        return Notification::where('type', 'review_requested')
            ->where('related_id', $prId)
            ->where('completed', false)
            ->update(['completed' => true]);
    }

    /**
     * Resolve pr_review notifications when PR is merged
     */
    public static function resolveOnPrMerged(int $prId): int
    {
        return Notification::where('type', 'pr_review')
            ->where('related_id', $prId)
            ->where('completed', false)
            ->update(['completed' => true]);
    }

    /**
     * Resolve pr_review notifications when review is dismissed
     */
    public static function resolveOnReviewDismissed(int $prId): int
    {
        return Notification::where('type', 'pr_review')
            ->where('related_id', $prId)
            ->where('completed', false)
            ->update(['completed' => true]);
    }

    /**
     * Resolve item_comment and comment_mention when user comments
     */
    public static function resolveOnUserCommented(
        string $itemType,
        int $itemId,
        int $commenterId
    ): int {
        $configured_user_id = config('app.GithubConfig')::USERID;

        if ($commenterId !== $configured_user_id) {
            return 0;
        }

        $resolvableTypes = ['item_comment', 'comment_mention'];

        return Notification::whereIn('type', $resolvableTypes)
            ->where('related_id', $itemId)
            ->where('completed', false)
            ->update(['completed' => true]);
    }

    /**
     * Resolve workflow_failed when new commit is pushed (workflow will re-run)
     */
    public static function resolveOnNewCommit(int $prId): int
    {
        return Notification::where('type', 'workflow_failed')
            ->where('related_id', $prId)
            ->where('completed', false)
            ->update(['completed' => true]);
    }

    /**
     * Resolve item_assigned when item is unassigned from user
     */
    public static function resolveOnUnassigned(int $itemId, int $userId): int
    {
        return Notification::where('type', 'item_assigned')
            ->where('related_id', $itemId)
            ->where('completed', false)
            ->update(['completed' => true]);
    }

    /**
     * Check if auto-resolution is enabled for a notification type
     */
    public static function isEnabledForType(string $notificationType): bool
    {
        $config = \App\GithubConfig::NOTIFICATION_AUTO_RESOLVE;

        if (!($config['enabled'] ?? true)) {
            return false;
        }

        return $config['types'][$notificationType]['enabled'] ?? false;
    }
}
