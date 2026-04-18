<?php

namespace App\Listeners;

use App\Events\ReleaseWebhookReceived;
use App\Models\Release;
use App\Models\Repository;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessReleaseWebhook implements ShouldQueue
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
    public function handle(ReleaseWebhookReceived $event): bool
    {
        $payload = $event->payload;

        if (! $payload || ! isset($payload->release) || ! isset($payload->repository)) {
            return false;
        }

        $releaseData = $payload->release;

        $repoFullName = $payload->repository->full_name;
        $repository = Repository::where('full_name', $repoFullName)->first();

        if (! $repository) {
            // Repository not found, skip processing
            return false;
        }

        Release::updateOrCreate(
            ['github_id' => $releaseData->id],
            [
                'repository_id' => $repository->id,
                'name' => $releaseData->name,
                'description' => $releaseData->body,
                'author_id' => $releaseData->author->id ?? null,
                'status' => $releaseData->draft ? 'draft' : 'published',
            ]
        );

        return true;
    }
}
