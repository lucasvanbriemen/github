<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    public function show(Request $request, string $path)
    {
        $path = ltrim($path, '/');

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        // Stream file with correct headers; inline display
        $mime = Storage::disk('public')->mimeType($path) ?: 'application/octet-stream';
        $filename = basename($path);

        return new Response(
            Storage::disk('public')->get($path),
            200,
            [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'Cache-Control' => 'public, max-age=31536000',
            ]
        );
    }
}

