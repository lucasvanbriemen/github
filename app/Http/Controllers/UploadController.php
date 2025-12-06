<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class UploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'files' => ['required', 'array'],
            'files.*' => ['file', 'mimetypes:image/*,video/*', 'max:51200'], // 50MB per file
        ]);

        $disk = Storage::disk('public');
        $datePrefix = date('Y/m');
        $pathPrefix = "uploads/{$datePrefix}";

        $out = [];

        foreach ($request->file('files', []) as $file) {
            $original = $file->getClientOriginalName();
            $ext = $file->getClientOriginalExtension();
            $basename = pathinfo($original, PATHINFO_FILENAME);
            $safeBase = Str::slug($basename);
            $safeBase = $safeBase !== '' ? $safeBase : 'file';
            $filename = $safeBase . '-' . Str::random(8) . ($ext ? ".{$ext}" : '');

            $storedPath = $file->storeAs($pathPrefix, $filename, 'public');

            $out[] = [
                'name' => $original,
                'type' => $file->getMimeType(),
                // Expose via dedicated media route, avoids SPA catch-all and server config
                'url'  => route('media.show', ['path' => $storedPath]),
                'path' => $storedPath,
                'size' => $file->getSize(),
            ];
        }

        return response()->json(['files' => $out]);
    }
}
