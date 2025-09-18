<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Issue;

class IncomingWebhookController extends Controller
{
    public $ISSUE_RELATED = ["issues"];

    public function index(Request $request)
    {
        $headers = $request->headers->all();
        $payload = json_decode($request->getContent(), false);

        $eventType = $headers['x-github-event'][0] ?? 'unknown';

        if (in_array($eventType, $this->ISSUE_RELATED)) {$this->issue($payload);}

        return response()->json(["message" => "received", "event" => $eventType], 200);
    }

    public function issue($payload)
    {
        $issueData = $payload->issue;
        $repositoryData = $payload->repository;
        $userData = $issueData->user;

        // Create the issue in the database
        Issue::updateOrCreate(
            ['github_id' => $issueData->id],
            [
                'repository_id' => $repositoryData->id,
                'opened_by_id' => $userData->id,
                'number' => $issueData->number,
                'title' => $issueData->title,
                'body' => $issueData->body ?? '',
                'state' => $issueData->state,
                'labels' => [],
                'assignees' => [],
            ]
        );

        return true;
    }
}
