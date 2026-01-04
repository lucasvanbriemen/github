<?php

namespace App\Listeners;

use App\Events\PushWebhookReceived;
use App\Events\PullRequestUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Commit;
use App\Models\GithubUser;
use App\Models\Branch;
use App\Models\Repository;
use App\Models\PullRequest;
use Illuminate\Support\Facades\DB;

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
        }

        // Check if this push affects any open PRs
        $affectedPRs = PullRequest::where('repository_id', $repository->id)
            ->whereHas('details', function($query) use ($branchName) {
                $query->where('head_branch', $branchName);
            })
            ->whereIn('state', ['open', 'draft'])
            ->get();

        foreach ($affectedPRs as $pr) {
            // Update PR's head_sha to latest commit
            $latestCommit = $payload->head_commit ?? end($payload->commits);

            if ($latestCommit) {
                DB::table('pull_requests')
                    ->where('id', $pr->id)
                    ->update([
                        'head_sha' => $latestCommit->id,
                        'updated_at' => now(),
                    ]);
            }

            // Broadcast the update
            event(new PullRequestUpdated(
                $pr->fresh(),
                'push',
                [
                    'commit_count' => count($payload->commits ?? []),
                    'pusher' => $payload->pusher->name ?? 'Unknown',
                    'commits' => array_map(fn($c) => [
                        'sha' => $c->id,
                        'message' => $c->message,
                        'author' => $c->author->name ?? 'Unknown'
                    ], $payload->commits ?? [])
                ]
            ));
        }
    }
}
