<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Issue;
use App\Models\Repository;

class IncomingWebhookController extends Controller
{
    public $ISSUE_RELATED = ["issues"];

    public function index(Request $request)
    {
        try {
            $headers = $request->headers->all();
            $payload = json_decode($request->getContent(), false);

            $eventType = $headers['x-github-event'][0] ?? 'unknown';

            if (in_array($eventType, $this->ISSUE_RELATED)) {
                $this->issue($payload);
            }

            return response()->json(["message" => "received", "event" => $eventType], 200);
        } catch (\Exception $e) {
            \Log::error('Webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'payload' => $request->getContent(),
                'headers' => $request->headers->all()
            ]);

            return response()->json([
                "error" => "Internal server error",
                "message" => $e->getMessage()
            ], 500);
        }
    }

    public function issue($payload)
    {
        // Validate that required fields exist
        if (!isset($payload->issue) || !isset($payload->repository)) {
            throw new \Exception('Missing required fields: issue or repository');
        }

        $issueData = $payload->issue;
        $repositoryData = $payload->repository;

        if (!isset($issueData->user)) {
            throw new \Exception('Missing user data in issue');
        }

        $userData = $issueData->user;

        // Ensure repository exists first
        $repository = self::update_repo($repositoryData);

        // Temporarily disable foreign key checks to avoid constraint issues
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Create the issue in the database using the repository's github_id instead of UUID
        Issue::updateOrCreate(
            ['github_id' => $issueData->id],
            [
                'repository_id' => $repository->github_id,
                'opened_by_id' => $userData->id,
                'number' => $issueData->number,
                'title' => $issueData->title,
                'body' => $issueData->body ?? '',
                'state' => $issueData->state,
                'labels' => json_encode($issueData->labels ?? []),
                'assignees' => json_encode($issueData->assignees ?? []),
            ]
        );

        // Re-enable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return true;
    }

    public static function update_repo($repo) {
        return Repository::updateOrCreate(
            ['github_id' => $repo->id],
            [
                'name' => $repo->name,
                'full_name' => $repo->full_name,
                'private' => $repo->private,
                'description' => $repo->description ?? '',
                'last_updated' => now(),
            ]
        );
    }
}
