<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use App\Models\IncommingWebhook;
use App\GithubConfig;

use function Fuse\Core\config;

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

        IncommingWebhook::create([
            'event'   => $eventType,
            'payload' => $raw,
        ]);

        if (!class_exists($class)) {
            return response()->json([
                'message' => 'Event class not found',
                'event'   => $eventType,
            ], 400);
        }
 
        Event::dispatch(new $class($payload));

        return response()->json([
            'message' => 'received',
            'event'   => $eventType,
        ]);
    }

    // For the extension we want to check if the Github url maps to an exsiting GUI url
    public function checkEndPoint(Request $request)
    {
        $url = $request->input('url'); // incoming GitHub URL
        $path = parse_url($url, PHP_URL_PATH); // e.g., /lucasvanbriemen/github/pulls
        $redirectUrl = null;

        foreach (GithubConfig::GITHUB_ROUTE_MAPPING as $pattern => $replacement) {
            $regex = preg_quote($pattern, '/');

            // Convert placeholders to regex
            $regex = str_replace(
                ['\:organization', '\:repository', '\*'],
                ['(?P<organization>[^/]+)', '(?P<repository>[^/]+)', '.*'],
                $regex
            );

            if (preg_match('/^' . $regex . '$/', $path, $matches)) {
                // Only allow if repo is in ALLOWED_REPOSITORIES
                if (isset($matches['organization'], $matches['repository'])) {
                    $fullRepo = $matches['organization'] . '/' . $matches['repository'];
                    if (!in_array($fullRepo, GithubConfig::ALLOWED_REPOSITORIES)) {
                        break; // not allowed, skip redirect
                    }
                }

                // Replace placeholders in replacement URL
                $redirectUrl = $replacement;
                if (isset($matches['organization'])) {
                    $redirectUrl = str_replace(':organization', $matches['organization'], $redirectUrl);
                }
                if (isset($matches['repository'])) {
                    $redirectUrl = str_replace(':repository', $matches['repository'], $redirectUrl);
                }
                break;
            }

            if ($redirectUrl !== null) {
                $redirectUrl = config('app.url') . ltrim($redirectUrl, '/');
            }
        }

        return response()->json([
            'redirect' => $redirectUrl !== null,
            'URL' => $redirectUrl ?? 'https://github.lucasvanbriemen.nl/',
        ]);
    }
}
