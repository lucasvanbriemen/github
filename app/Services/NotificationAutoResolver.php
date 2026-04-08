<?php

declare(strict_types=1);

namespace App\Services;

use App\GithubConfig;
use App\Models\Notification;

class NotificationAutoResolver
{
    public static function resolveTrigger(string $trigger, int $itemId): int
    {
        $config = GithubConfig::NOTIFICATION_AUTO_RESOLVE;
        $notificationTypes = $config[$trigger] ?? [];

        return Notification::whereIn('type', $notificationTypes)
            ->where('related_id', $itemId)
            ->where('completed', false)
            ->update(['completed' => true]);
    }

    public static function resolveForComment(int $commentId): int
    {
        $config = GithubConfig::NOTIFICATION_AUTO_RESOLVE;
        $notificationTypes = $config['comment_resolve'] ?? [];

        return Notification::whereIn('type', $notificationTypes)
            ->where('related_id', $commentId)
            ->where('completed', false)
            ->update(['completed' => true]);
    }
}
