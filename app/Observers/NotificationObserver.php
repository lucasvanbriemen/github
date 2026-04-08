<?php

namespace App\Observers;

use App\Models\Notification;

class NotificationObserver
{
    public function created(Notification $notification): void
    {
        $notification->loadRelatedData();

        $count = Notification::where('completed', false)->count();

        Ably::send('notifications', [
            'count' => $count,
            'subject' => $notification->subject(),
            'type' => $notification->type,
        ]);
    }

    public function updated(Notification $notification): void
    {
        if ($notification->wasChanged('completed')) {
            $count = Notification::where('completed', false)->count();

            Ably::send('notifications', [
                'count' => $count,
            ]);
        }
    }
}
