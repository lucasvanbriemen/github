<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImageProxyController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\PullRequestController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RepositoryController;
use App\Http\Middleware\IsLoggedIn;
use App\Models\PullRequestReview;
use Illuminate\Support\Facades\Route;
use App\Mail\PullRequestReviewed;
use App\GithubConfig;
use Illuminate\Support\Facades\Mail;

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

            Route::get('/prs/{pull_request}', [PullRequestController::class, 'show'])
                ->name('repository.prs.show');

            Route::get('/prs/{pull_request}/files', [PullRequestController::class, 'showFiles'])
                ->name('repository.prs.show.files');
        });
    });
});

Route::get('/mail_preview/PullRequestReviewed', function () {
    $pullRequestReview = PullRequestReview::find(3297960164);

    Mail::to("vanbriemenluacs@gmail.com")
        ->send(new PullRequestReviewed($pullRequestReview));

    
    return new PullRequestReviewed(PullRequestReview::find(3297960164));
})->where('mailable', '[A-Za-z]+')->name('mail_preview');