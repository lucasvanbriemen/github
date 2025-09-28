<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class IncomingWebhookController extends Controller
{
    public function index(Request $request)
    {
        $raw = $request->input('payload', $request->getContent() ?: '{}');
        $payload = json_decode($raw, false, 512, JSON_THROW_ON_ERROR);

        $eventType = $request->header('x-github-event')
            ?? $request->input('x_github_event')
            ?? $request->input('event', 'unknown');

        // Convert snake_case event names to StudlyCase
        $studly = Str::studly($eventType);

        // Build the event class dynamically
        $class = "App\\Events\\{$studly}WebhookReceived";

        if (class_exists($class)) {
            Event::dispatch(new $class($payload));
        }

        return response()->json([
            'message' => 'received',
            'event'   => $eventType,
        ]);
    }
}
