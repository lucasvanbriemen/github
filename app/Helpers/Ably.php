<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class Ably
{
    public static function send(string $channel, mixed $data, string $event = 'event'): bool
    {
        $apiKey = config('services.ably.main_key');

        $response = Http::withBasicAuth(...explode(':', $apiKey, 2))
            ->post('https://rest.ably.io/channels/'.urlencode($channel).'/messages', [
                'name' => $event,
                'data' => is_string($data) ? $data : json_encode($data),
            ]);

        return $response->successful();
    }
}
