<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        
    }
}
