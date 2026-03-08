<?php

namespace App\Listeners;

use App\Events\DeleteWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Branch;

class ProcessDeleteWebhook implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DeleteWebhookReceived $event): void
    {
        $payload = $event->payload;

        if (isset($payload->ref_type) && $payload->ref_type === 'branch' && isset($payload->ref)) {
            Branch::destroy(
                [
                    'name' => $payload->ref,
                    'repository_id' => $payload->repository->id
                ]
            );
        }
    }
}
