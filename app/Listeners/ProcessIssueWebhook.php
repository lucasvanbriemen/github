<?php

namespace App\Listeners;

use App\Events\IssuesWebhookReceived;
use App\Models\GithubUser;
use App\Models\Issue;
use App\Models\Repository;
use App\Models\ItemLabel;
use App\Models\Notification;

class ProcessIssueWebhook
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
    public function handle(IssuesWebhookReceived $event): bool
    {
        $payload = $event->payload;

        if (!$payload || !isset($payload->issue) || !isset($payload->repository)) {
            return false;
        }
        $issueData = $payload->issue;
        $repositoryData = $payload->repository;

        $userData = $issueData->user;

        $repository = Repository::updateFromWebhook($repositoryData);

        // Issue author
        GithubUser::updateFromWebhook($userData);

        $assigneeGithubIds = [];
        // We have to loop over the assignees to create/update them in the github_users table
        if (!empty($issueData->assignees) && is_array($issueData->assignees)) {
            foreach ($issueData->assignees as $assignee) {
                $assigneeGithubIds[] = $assignee->id;

                // Create/update the assignee in github_users table
                GithubUser::updateFromWebhook($assignee);
            }
        }

        // If its a pull request, we ignore it
        if (isset($issueData->pull_request)) {
            return true;
        }

        // check if the issue already exists
        $issue = Issue::where('id', $issueData->id)->first();
        if (!$issue) {
            $preHookAssigned = false;
        } else {
            $preHookAssigned = $issue->isCurrentlyAssignedToUser();
        }

        $issue = Issue::updateOrCreate(
            ['id' => $issueData->id],
            [
                'repository_id' => $repository->id,
                'opened_by_id' => $userData->id,
                'number' => $issueData->number,
                'title' => $issueData->title,
                'body' => $issueData->body ?? '',
                'state' => $issueData->state
            ]
        );

        // Sync assignees in the pivot table
        $issue->assignees()->sync($assigneeGithubIds);

        $current_labels = ItemLabel::where('item_id', $issue->id)->pluck('label_id')->toArray();
        $new_labels = [];
        if (!empty($issueData->labels) && is_array($issueData->labels)) {
            foreach ($issueData->labels as $labelData) {
                // Find the label in the labels table
                $label = \App\Models\Label::where('github_id', $labelData->id)
                    ->where('repository_id', $repository->id)
                    ->first();

                if ($label) {
                    $new_labels[] = $label->id;
                }
            }
        }

        $missing_labels = array_diff($new_labels, $current_labels);
        foreach ($missing_labels as $label_id) {
            ItemLabel::create([
                'item_id' => $issue->id,
                'label_id' => $label_id
            ]);
        }

        $deleted_labels = array_diff($current_labels, $new_labels);
        foreach ($deleted_labels as $label_id) {
            ItemLabel::where('item_id', $issue->id)
                ->where('label_id', $label_id)
                ->delete();
        }

        $currentlyAssigned = $issue->isCurrentlyAssignedToUser();
        if ($currentlyAssigned && !$preHookAssigned) {
            Notification::create([
                'type' => 'item_assigned',
                'related_id' => $issue->id
            ]);
        }

        return true;
    }
}
