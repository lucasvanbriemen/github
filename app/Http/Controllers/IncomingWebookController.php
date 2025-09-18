<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Issue;

class IncomingWebookController extends Controller
{
    public $ISSUE_RELATED = ["issues"];

    public function index(Request $request)
    {
        $headers = $request->headers->all();
        $payload = (object) $request->all();

        $eventType = $headers['x-github-event'][0] ?? 'unknown';

        if (in_array($eventType, $this->ISSUE_RELATED)) {$this->issue($payload);}

        return response()->json(["message" => "received", "event" => $eventType], 200);
    }

    public function issue($payload)
    {
        $issueData = $payload->issue;
        $repositoryData = $payload->repository;
        $userData = $issueData->user;

        // Find or create the issue in the database
        Issue::updateOrCreate(
            ['github_id' => $issueData->id],
            [
                'repository_id' => $repositoryData->id,
                'opened_by_id' => $userData->id,
                'number' => $issueData->number,
                'title' => $issueData->title,
                'body' => $issueData->body,
                'last_updated' => $issueData->updated_at,
                'state' => $issueData->state,
                'labels' => array_map(fn($label) => $label->name, $issueData->labels) ?? [],
                'assignees' => array_map(fn($assignee) => ['login' => $assignee->login, 'id' => $assignee->id], $issueData->assignees) ?? [],
            ]
        );
    }
}
