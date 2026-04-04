<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class AblyTestController extends Controller
{
    public function publishChannel1(): JsonResponse
    {
        return $this->publish('channel-1', 'event', 'Hello from channel 1!');
    }

    public function publishChannel2(): JsonResponse
    {
        return $this->publish('channel-2', 'event', 'Hello from channel 2!');
    }

    private function publish(string $channel, string $event, string $message): JsonResponse
    {
        $apiKey = config('services.ably.main_key');

        $response = Http::withBasicAuth(...explode(':', $apiKey, 2))
            ->post("https://rest.ably.io/channels/{$channel}/messages", [
                'name' => $event,
                'data' => $message,
            ]);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'channel' => $channel,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $response->body(),
        ], $response->status());
    }
}
