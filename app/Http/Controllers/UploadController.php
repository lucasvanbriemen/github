<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $datePrefix = date('Y/m/d');
        $pathPrefix = "uploads/{$datePrefix}";

        $out = [];

        foreach ($request->file('files', []) as $file) {
            $original = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();

            $basename = pathinfo($original, PATHINFO_FILENAME);
            $safeBase = Str::slug($basename);
            $safeBase = $safeBase !== '' ? $safeBase : 'file';

            // example: media/uploads/2025/12/25/image-ysS4TjId.png
            $filename = $safeBase . '-' . Str::random(8) . ($extension ? ".{$extension}" : '');
            $storedPath = $file->storeAs($pathPrefix, $filename, 'public');

            $out[] = [
                'name' => $original,
                'type' => $file->getMimeType(),
                'url'  => route('media.show', ['path' => $storedPath]),
                'path' => $storedPath,
                'size' => $file->getSize(),
            ];
        }

        return response()->json(['files' => $out]);
    }

    public function show(Request $request, string $path)
    {
        $path = ltrim($path, '/');

        // Stream file with correct headers; inline display
        $mime = Storage::disk('public')->mimeType($path) ?: 'application/octet-stream';
        $filename = basename($path);

        return new Response(
            Storage::disk('public')->get($path),
            200,
            [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Cache-Control' => 'public, max-age=31536000',
            ]
        );
    }
}
