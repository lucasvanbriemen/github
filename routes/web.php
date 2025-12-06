<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\IsLoggedIn;
use App\Http\Controllers\ImageProxyController;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\MediaController;

// To proxy images from private repositories
Route::get('/proxy/image', [ImageProxyController::class, 'proxy'])->name('image.proxy');

// Serve media stored on the public disk via a dedicated route
Route::get('/media/{path}', [MediaController::class, 'show'])
    ->where('path', '.*')
    ->name('media.show');

// SPA Entry Point - Serve the SPA for all routes
// All routing is handled client-side by Svelte
Route::middleware(IsLoggedIn::class)->group(function () {
    Route::get('/{any?}', function () {
        return view('spa');
    })->where('any', '.*')->name('spa');
});
