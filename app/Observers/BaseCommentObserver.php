<?php

namespace App\Observers;

use App\Models\BaseComment;
use App\GithubConfig;
use App\Models\Notification;

class BaseCommentObserver
{
    /**
     * Handle the BaseComment "created" event.
     */
    public function created(BaseComment $baseComment): void
    {
        $this->handle($baseComment);
    }

    /**
     * Handle the BaseComment "updated" event.
     */
    public function updated(BaseComment $baseComment): void
    {
        $this->handle($baseComment);
    }

    /**
     * Handle the BaseComment "deleted" event.
     */
    public function deleted(BaseComment $baseComment): void
    {
        //
    }

    /**
     * Handle the BaseComment "restored" event.
     */
    public function restored(BaseComment $baseComment): void
    {
        //
    }

    /**
     * Handle the BaseComment "force deleted" event.
     */
    public function forceDeleted(BaseComment $baseComment): void
    {
        //
    }

    private function handle(BaseComment $baseComment) {
        if (stripos($baseComment->body, GithubConfig::USERNAME) === false) {
            // return;
        }

        Notification::create([
            'type' => 'comment_mention',
            'related_id' => $baseComment->id
        ]);
    }
}
