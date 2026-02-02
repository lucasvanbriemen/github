<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;

class NotificationAutoResolver
{
    /**
     * Resolve notifications for a trigger
     */
    public static function resolveTrigger(string $trigger, int $itemId): int
    {
        $config = \App\GithubConfig::NOTIFICATION_AUTO_RESOLVE;

        if (!($config['enabled'] ?? false)) {
            return 0;
        }

        $triggerConfig = $config['triggers'][$trigger] ?? null;
        if (!$triggerConfig || !($triggerConfig['enabled'] ?? false)) {
            return 0;
        }

        $notificationTypes = $triggerConfig['resolves'] ?? [];
        if (empty($notificationTypes)) {
            return 0;
        }

        return Notification::whereIn('type', $notificationTypes)
            ->where('related_id', $itemId)
            ->where('completed', false)
            ->update(['completed' => true]);
    }

    /**
     * Resolve specific notification types for an item
     */
    public static function resolveNotificationTypes(
        int $itemId,
        array $notificationTypes
    ): int {
        if (empty($notificationTypes)) {
            return 0;
        }

        return Notification::whereIn('type', $notificationTypes)
            ->where('related_id', $itemId)
            ->where('completed', false)
            ->update(['completed' => true]);
    }

    /**
     * Check if a trigger is enabled
     */
    public static function isTriggerEnabled(string $trigger): bool
    {
        $config = \App\GithubConfig::NOTIFICATION_AUTO_RESOLVE;
        return $config['triggers'][$trigger]['enabled'] ?? false;
    }
}
