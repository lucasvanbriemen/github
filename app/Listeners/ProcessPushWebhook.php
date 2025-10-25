<?php

namespace App\Listeners;

use App\Events\PushWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Commit;
use App\Models\GithubUser;
use App\Models\Branch;
use App\Models\Repository;
use App\Models\ViewedFile;

class ProcessPushWebhook // implements ShouldQueue
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
    public function handle(PushWebhookReceived $event)
    {
        $payload = $event->payload;

        // Ensure the repository exists
        $repository = Repository::updateOrCreate(
            ['id' => $payload->repository->id],
            [
                'name' => $payload->repository->name,
                'full_name' => $payload->repository->full_name,
                'private' => $payload->repository->private,
                'html_url' => $payload->repository->html_url,
                'description' => $payload->repository->description,
                'owner_login' => $payload->repository->owner->login,
                'last_updated' => now(),
            ]
        );

        // Ensure the branch exists
        $branchName = str_replace('refs/heads/', '', $payload->ref ?? '');
        $branch = Branch::updateOrCreate(
            [
                'name' => $branchName,
                'repository_id' => $repository->id
            ],
            ['updated_at' => now()]
        );

        // If there are no commits, we can stop here
        if (!isset($payload->commits) || empty($payload->commits)) {
            return;
        }

        // Process commits
        foreach ($payload->commits as $commitData) {
            $author = GithubUser::where('name', $commitData->author->username)->first();
            if (!$author) {
                return;
            }

            // Create or update the commit
            Commit::updateOrCreate(
                ['sha' => $commitData->id],
                [
                    'repository_id' => $repository->id,
                    'branch_id' => $branch->id,
                    'user_id' => $author->id,
                    'message' => $commitData->message,
                ]
            );

            foreach (array_merge($commitData->added, $commitData->modified, $commitData->removed) as $filePath) {
                ViewedFile::where('branch_id', $branch->id)
                    ->where('file_path', $filePath)
                    ->delete();
            }
        }
    }
}
