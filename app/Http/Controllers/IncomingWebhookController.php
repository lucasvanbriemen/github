<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Issue;
use App\Models\Repository;
use App\Models\Console;

class IncomingWebhookController extends Controller
{
    public $ISSUE_RELATED = ["issues"];

    public function index(Request $request)
    {
        $headers = $request->headers->all();
        $payload = $request->getContent();
        $payload = json_decode($payload ?? '{}');
        
        $eventType = $headers['x-github-event'][0] ?? 'unknown';
        Console::create(["command" => json_encode($payload), "successful" => true, "executed_at" => now()]);
        
        if (in_array($eventType, $this->ISSUE_RELATED)) {
            $this->issue($payload);
        }

        return response()->json(["message" => "received", "event" => $eventType, "payload" => $payload], 200);
    }

    public function issue($payload)
    {
        $issueData = $payload->issue;
        $repositoryData = $payload->repository;

        $userData = $issueData->user;

        // Ensure repository exists first
        $repository = self::update_repo($repositoryData);

        // Create the issue in the database using the repository's github_id instead of UUID
        Issue::updateOrCreate(
        ['github_id' => $issueData->id],
        [
            'repository_id' => $repository->id,   // use local DB primary key
            'opened_by_id' => $userData->id,
            'number' => $issueData->number,
            'title' => $issueData->title,
            'body' => $issueData->body ?? '',
            'state' => $issueData->state,
            'labels' => json_encode($issueData->labels ?? []),
            'assignees' => json_encode($issueData->assignees ?? []),
        ]);

        return true;
    }

    public static function update_repo($repo) {
        return Repository::updateOrCreate(
            ['github_id' => $repo->id],
            [
                'organization_id' => $repo->owner->id,
                'name' => $repo->name,
                'full_name' => $repo->full_name,
                'private' => $repo->private,
                'description' => $repo->description ?? '',
                'last_updated' => now(),
            ]
        );
    }
}
