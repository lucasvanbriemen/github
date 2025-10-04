<?php

use App\Http\Controllers\IncomingWebhookController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\PullRequestController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RepositoryController;
use App\Http\Middleware\IsLoggedIn;
use Illuminate\Support\Facades\Route;

Route::middleware(IsLoggedIn::class)->group(function () {
    Route::patch('/organizations', [OrganizationController::class, 'updateOrganizations'])
        ->name('organizations.update');

    Route::prefix('organization/{organization}/{repository}')->group(function () {
        Route::get('tree/{file_path?}', [RepositoryController::class, 'show_file_tree'])
            ->where('file_path', '.*')
            ->name('api.repositories.show');

        Route::prefix('issues')->group(function () {
            Route::get('/', [IssueController::class, 'getIssues'])
                ->name('api.repositories.issues');

            Route::prefix('{issue}')->group(function () {

                Route::get('linked_pull_requests', [IssueController::class, 'getLinkedPullRequestsHtml'])
                    ->name('api.repositories.issues.issue');

                Route::prefix('comments/{comment}')->group(function () {
                    Route::patch('resolve', [IssueController::class, 'resolveComment'])
                        ->name('api.repositories.issues.comment.resolve');

                    Route::patch('unresolve', [IssueController::class, 'unresolveComment'])
                        ->name('api.repositories.issues.comment.unresolve');
                });
            });
        });

        Route::prefix('pull_requests')->group(function () {
            Route::get('/', [PullRequestController::class, 'getPullRequests'])
                ->name('api.repositories.pull_requests');

            Route::prefix('{pullRequest}')->group(function () {

                Route::get('linked_issues', [PullRequestController::class, 'getLinkedIssuesHtml'])
                    ->name('api.repositories.pull_requests.pull_request');

                Route::prefix('comments/{comment}')->group(function () {
                    Route::patch('resolve', [PullRequestController::class, 'resolveComment'])
                        ->name('api.repositories.pull_requests.comment.resolve');
                    Route::patch('unresolve', [PullRequestController::class, 'unresolveComment'])
                        ->name('api.repositories.pull_requests.comment.unresolve');
                });

                Route::prefix('review/{comment}')->group(function () {
                    Route::patch('resolve', [PullRequestController::class, 'resolveReviewComment'])
                        ->name('api.repositories.pull_requests.review.resolve');
                    Route::patch('unresolve', [PullRequestController::class, 'unresolveReviewComment'])
                        ->name('api.repositories.pull_requests.review.unresolve');
                });
            });
        });
    });
});

Route::any('incoming_hook', [IncomingWebhookController::class, 'index'])
    ->name('api.webhook');

    
Route::any('check_end_point', function () {
    return response()->json(['redirect' => true, 'url' => route('dashboard')]);
})->name('api.endpoint.check');
