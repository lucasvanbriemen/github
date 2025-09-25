<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImageProxyController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\PullRequestController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RepositoryController;
use App\Http\Middleware\IsLoggedIn;
use Illuminate\Support\Facades\Route;

Route::middleware(IsLoggedIn::class)->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/webhook_sender', function () {
        return view('webhook_sender');
    })->name('webhook_sender');

    Route::get('/proxy/image', [ImageProxyController::class, 'proxy'])->name('image.proxy');

    Route::prefix('organization/{organization}')->group(function () {
        Route::get('/', [OrganizationController::class, 'show'])->name('organization.show');

        Route::prefix('{repository}')->group(function () {
            Route::redirect('/', './tree');

            Route::get('/tree/{file_path?}', [RepositoryController::class, 'show'])
                ->where('file_path', '(.*)?')
                ->name('repository.show');

            Route::get('/issues', [IssueController::class, 'index'])
                ->name('repository.issues.index');

            Route::get('/issues/{issue}', [IssueController::class, 'show'])
                ->name('repository.issues.show');

            Route::get('/prs', [PullRequestController::class, 'index'])
                ->name('repository.prs.index');

        });
    });
});
