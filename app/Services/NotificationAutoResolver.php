<?php

declare(strict_types=1);

namespace App\Services;

use App\GithubConfig;
use App\Helpers\Ably;
use App\Models\BaseComment;
use App\Models\Notification;
use App\Models\PullRequestReview;

class NotificationAutoResolver
{
    /**
     * Notification types where related_id points directly to the Item ID.
     */
    private const ITEM_DIRECT_TYPES = ['item_assigned', 'review_requested'];

    /**
     * Notification types where related_id points to a BaseComment ID.
     */
    private const COMMENT_BASED_TYPES = ['item_comment', 'comment_mention'];

    /**
     * Notification types where related_id points to a PullRequestReview ID.
     */
    private const REVIEW_BASED_TYPES = ['pr_review'];

    public static function resolveTrigger(string $trigger, int $itemId): int
    {
        $config = GithubConfig::NOTIFICATION_AUTO_RESOLVE;
        $notificationTypes = $config[$trigger] ?? [];

        if (empty($notificationTypes)) {
            return 0;
        }

        $resolved = 0;

        // Resolve notifications where related_id is the item ID directly
        $directTypes = array_intersect($notificationTypes, self::ITEM_DIRECT_TYPES);
        if (! empty($directTypes)) {
            $resolved += Notification::whereIn('type', $directTypes)
                ->where('related_id', $itemId)
                ->where('completed', false)
                ->update(['completed' => true]);
        }

        // Resolve notifications where related_id is a BaseComment ID (comment on this item)
        $commentTypes = array_intersect($notificationTypes, self::COMMENT_BASED_TYPES);
        if (! empty($commentTypes)) {
            $commentIds = BaseComment::where('issue_id', $itemId)->pluck('id');
            if ($commentIds->isNotEmpty()) {
                $resolved += Notification::whereIn('type', $commentTypes)
                    ->whereIn('related_id', $commentIds)
                    ->where('completed', false)
                    ->update(['completed' => true]);
            }
        }

        // Resolve notifications where related_id is a PullRequestReview ID
        $reviewTypes = array_intersect($notificationTypes, self::REVIEW_BASED_TYPES);
        if (! empty($reviewTypes)) {
            $reviewIds = PullRequestReview::whereHas('baseComment', function ($q) use ($itemId) {
                $q->where('issue_id', $itemId);
            })->pluck('id');
            if ($reviewIds->isNotEmpty()) {
                $resolved += Notification::whereIn('type', $reviewTypes)
                    ->whereIn('related_id', $reviewIds)
                    ->where('completed', false)
                    ->update(['completed' => true]);
            }
        }

        if ($resolved > 0) {
            self::broadcastCount();
        }

        return $resolved;
    }

    /**
     * Resolve notifications that are directly related to a specific comment.
     */
    public static function resolveForComment(int $commentId): int
    {
        $resolved = Notification::whereIn('type', self::COMMENT_BASED_TYPES)
            ->where('related_id', $commentId)
            ->where('completed', false)
            ->update(['completed' => true]);

        if ($resolved > 0) {
            self::broadcastCount();
        }

        return $resolved;
    }

    private static function broadcastCount(): void
    {
        $count = Notification::where('completed', false)->count();
        Ably::send('notifications', ['count' => $count]);
    }
}
