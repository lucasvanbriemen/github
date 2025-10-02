<?php

namespace App\Listeners;

use App\Events\PushWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\ViewedFile;
use App\Models\PullRequest;

class ProcessPushWebhook implements ShouldQueue
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
    public function handle(PushWebhookReceived $event): void
    {
        $payload = $event->payload;

        // We have to update the files that have been viewed to false if the have been changed in the push
        $branch = $payload->ref;
        $branch = str_replace('refs/heads/', '', $branch);

        $githubRepoId = $payload->repository->id;

        $filesChanged = [];
        if (isset($payload->commits) && is_array($payload->commits)) {
            foreach ($payload->commits as $commit) {
                if (isset($commit->added) && is_array($commit->added)) {
                    $filesChanged = array_merge($filesChanged, $commit->added);
                }
                if (isset($commit->removed) && is_array($commit->removed)) {
                    $filesChanged = array_merge($filesChanged, $commit->removed);
                }
                if (isset($commit->modified) && is_array($commit->modified)) {
                    $filesChanged = array_merge($filesChanged, $commit->modified);
                }
            }
        }

        $pullRequest = PullRequest::where('repository_id', $repository->github_id)
            ->where('head_branch', $branch)
            ->first();

        if (!$pullRequest) {
            return;
        }

        foreach (array_unique($filesChanged) as $filePath) {
            $viewedFile = ViewedFile::where('pull_request_id', $pullRequest->github_id)
                ->where('file_path', $filePath)
                ->where('pull_request_id', $pullRequest->github_id)
                ->first();

            if ($viewedFile && $viewedFile->viewed) {
                // Mark as not viewed
                $viewedFile->viewed = false;
                $viewedFile->save();
            }
    }
}
