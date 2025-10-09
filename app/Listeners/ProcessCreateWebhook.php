<?php

namespace App\Listeners;

use App\Events\CreateWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Branch;


class ProcessCreateWebhook implements ShouldQueue
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
    public function handle(CreateWebhookReceived $event): void
    {
        $payload = $event->payload;

        if (isset($payload->ref_type) && $payload->ref_type === 'branch' && isset($payload->ref)) {
            // Process branch creation
            Branch::updateOrCreate(
                [
                    'name' => $payload->ref,
                    'repository_github_id' => $payload->repository->id
                ],
                ['updated_at' => now()]
            );
        }
    }
}
