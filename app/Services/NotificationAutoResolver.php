<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\GithubConfig;

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
}
