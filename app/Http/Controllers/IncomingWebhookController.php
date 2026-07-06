<?php

namespace App\Http\Controllers;

use App\GithubConfig;
use App\Models\IncommingWebhook;
use App\Models\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IncomingWebhookController extends Controller
{
    // Every incoming webhook is also forwarded to this URL.
    private const FORWARD_URL = 'https://git.ltvb.nl/incoming_hook';

    public function index(Request $request)
    {
        $raw = $request->input('payload', $request->getContent() ?: '{}');
        $this->forward($request, $raw);

        return "thanks for the webhook!";
        $payload = json_decode($raw, false, 512, JSON_THROW_ON_ERROR);

        $eventType = $request->header('x-github-event')
            ?? $request->input('x_github_event')
            ?? $request->input('event', 'unknown');

        // Convert snake_case event names to StudlyCase
        $studly = Str::studly($eventType);

        // Build the event class dynamically
        $class = "App\\Events\\{$studly}WebhookReceived";

        IncommingWebhook::create([
            'event' => $eventType,
            'payload' => $raw,
        ]);

        if (! class_exists($class)) {
            return response()->json([
                'message' => 'Event class not found',
                'event' => $eventType,
            ], 400);
        }

        Event::dispatch(new $class($payload));

        return response()->json([
            'message' => 'received',
            'event' => $eventType,
        ]);
    }

    // Relay the incoming webhook to the new domain, preserving the raw body
    // and GitHub's delivery headers. Failures here must never break local
    // processing, so everything is wrapped and logged.
    private function forward(Request $request, string $raw): void
    {
        $headers = array_filter([
            'Content-Type' => $request->header('content-type', 'application/json'),
            'X-GitHub-Event' => $request->header('x-github-event'),
            'X-GitHub-Delivery' => $request->header('x-github-delivery'),
            'X-GitHub-Hook-ID' => $request->header('x-github-hook-id'),
            'X-Hub-Signature' => $request->header('x-hub-signature'),
            'X-Hub-Signature-256' => $request->header('x-hub-signature-256'),
            'User-Agent' => $request->header('user-agent'),
        ]);

        try {
            Http::withHeaders($headers)
                ->timeout(5)
                ->withBody($raw, $headers['Content-Type'])
                ->post(self::FORWARD_URL);
        } catch (\Throwable $e) {
            Log::warning('Failed to forward incoming webhook', [
                'url' => self::FORWARD_URL,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // For the extension we want to check if the Github url maps to an exsiting GUI url
    public function checkEndPoint(Request $request)
    {
        $url = $request->input('url');
        $path = parse_url($url, PHP_URL_PATH) ?? '/';
        $redirectUrl = null;

        foreach (GithubConfig::GITHUB_ROUTE_MAPPING as $pattern => $replacement) {
            // Convert pattern to regex, capturing tail
            $regex = str_replace(
                [':organization', ':repository', '*'],
                ['(?P<organization>[^/]+)', '(?P<repository>[^/]+)', '(?P<tail>/.*)?'],
                $pattern
            );

            $regex = '#^'.$regex.'$#';

            if (! preg_match($regex, $path, $matches)) {
                continue;
            }

            // Enforce allowed repositories
            if (isset($matches['organization'], $matches['repository'])) {
                $repo = $matches['organization'].'/'.$matches['repository'];

                if (Repository::where('full_name', $repo)->doesntExist()) {
                    return response()->json([
                        'redirect' => false,
                        'URL' => 'https://github.lucasvanbriemen.nl/',
                    ]);
                }
            }

            // Build redirect URL including trailing path
            $redirectUrl = str_replace(
                [':organization', ':repository', '*'],
                [
                    $matches['organization'] ?? '',
                    $matches['repository'] ?? '',
                    $matches['tail'] ?? '',
                ],
                $replacement
            );

            break;
        }

        if ($redirectUrl !== null) {
            $redirectUrl = 'https://github.lucasvanbriemen.nl'.$redirectUrl;
        }

        return response()->json([
            'redirect' => $redirectUrl !== null,
            'URL' => $redirectUrl ?? 'https://github.lucasvanbriemen.nl/',
        ]);
    }
}
