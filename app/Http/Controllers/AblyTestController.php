<?php

namespace App\Http\Controllers;

use App\Helpers\Ably;
use Illuminate\Http\JsonResponse;

class AblyTestController extends Controller
{
    public function publishChannel1(): JsonResponse
    {
        $success = Ably::send('channel-1', 'Hello from channel 1!');

        return response()->json(['success' => $success, 'channel' => 'channel-1']);
    }

    public function publishChannel2(): JsonResponse
    {
        $success = Ably::send('channel-2', 'Hello from channel 2!');

        return response()->json(['success' => $success, 'channel' => 'channel-2']);
    }
}
