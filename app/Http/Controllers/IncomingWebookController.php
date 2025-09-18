<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Issue;

class IncomingWebookController extends Controller
{
    //
    public function index(Request $request)
    {
        $headers = $request->headers->all();
        $payload = $request->all();
        // Turn payload into object
        $payload = json_decode(json_encode($payload));

        $eventType = $headers['x-github-event'][0] ?? 'unknown';

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
                'labels' => array_map(fn($label) => $label->name, $issueData->labels),
            ]
        );
    }
}
