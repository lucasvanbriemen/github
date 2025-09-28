<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\IssueWebhookReceived;
use App\Events\PullRequestWebhookReceived;
use App\Events\PullRequestReviewWebhookReceived;
use App\Events\PullRequestReviewCommentWebhookReceived;
use App\Events\CommentWebhookReceived;

class IncomingWebhookController extends Controller
{
    public function index(Request $request)
    {
        $headers = $request->headers->all();
        $raw = $request->getContent();
        // Support HTML form testing where JSON is sent as a field
        if ($request->has('payload') && (!empty($request->input('payload')))) {
            $raw = $request->input('payload');
        }
        $payload = json_decode($raw ?: '{}');

        // Allow overriding event via form/query as well
        $eventType = $headers['x-github-event'][0] ?? $request->input('x_github_event', $request->input('event', 'unknown'));

        if ($eventType === "issues") {
            IssueWebhookReceived::dispatch($payload);
        }

        if ($eventType === "issue_comment") {
            CommentWebhookReceived::dispatch($payload);
        }

        if ($eventType === "pull_request") {
            PullRequestWebhookReceived::dispatch($payload);
        }

        if ($eventType === "pull_request_review") {
            PullRequestReviewWebhookReceived::dispatch($payload);
        }

        if ($eventType === "pull_request_review_comment") {
            PullRequestReviewCommentWebhookReceived::dispatch($payload);
        }

        return response()->json(['message' => 'received', 'event' => $eventType], 200);
    }
}
