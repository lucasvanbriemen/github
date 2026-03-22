<?php

namespace App\Listeners;

use App\Events\CreateWebhookReceived;
use App\Models\Branch;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessCreateWebhook implements ShouldQueue
{
    public function __construct()
    {
        //
    }

    public function handle(CreateWebhookReceived $event): void
    {
        $payload = $event->payload;

        if (isset($payload->ref_type) && $payload->ref_type === 'branch' && isset($payload->ref)) {
            // Process branch creation
            Branch::updateOrCreate(
                [
                    'name' => $payload->ref,
                    'repository_id' => $payload->repository->id,
                ],
                ['updated_at' => now()]
            );
        }
    }
}
