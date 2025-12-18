<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ImageProxyController extends Controller
{
    public function proxy(Request $request)
    {
        $imageUrl = $request->get('url');

        if (!$imageUrl) {
            abort(400, 'URL parameter required');
        }

        // Validate URL is from GitHub
        if (!str_starts_with($imageUrl, 'https://github.com') &&
            !str_starts_with($imageUrl, 'https://raw.githubusercontent.com') &&
            !str_starts_with($imageUrl, 'https://user-images.githubusercontent.com')) {
            abort(403, 'Only GitHub URLs allowed');
        }

        // Use GitHub token for authentication
        $token = config('services.github.access_token');
        if (!$token) {
            abort(500, 'GitHub token not configured');
        }

        $ch = curl_init($imageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'User-Agent: github-gui'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $imageData = curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$imageData) {
            abort(404, 'Image not found or access denied');
        }

        // Ensure it's an image content type
        if (!str_starts_with($contentType, 'image/')) {
            abort(400, 'Invalid content type');
        }

        return response($imageData)
            ->header('Content-Type', $contentType)
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Access-Control-Allow-Origin', '*');
    }
}