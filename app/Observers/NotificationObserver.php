<?php

namespace App\Observers;

use App\Models\Notification;
use App\Mail\NotificationCreated;
use App\GithubConfig;
use Illuminate\Support\Facades\Mail;

class NotificationObserver
{
    public function created(Notification $notification): void
    {
        // Send email to the configured GitHub user email
        Mail::to(GithubConfig::USER_EMAIL)->send(new NotificationCreated($notification));
    }
}
