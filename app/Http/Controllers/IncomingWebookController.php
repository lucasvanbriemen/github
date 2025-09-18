<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IncomingWebookController extends Controller
{
    //
    public function index(Request $request)
    {
        // Get the headers and payload
        $headers = $request->headers->all();
        $payload = $request->all();

        // Get the event type from headers
        $eventType = $headers['x-github-event'][0] ?? 'unknown';

        return response()->json(["message" => "received", "event" => $eventType], 200);
    }
}
