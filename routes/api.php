<?php

use App\Http\Controllers\IncomingWebhookController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RepositoryController;
use App\Http\Middleware\IsLoggedIn;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PullRequestController;
use App\Http\Controllers\BaseCommentController;
use App\Http\Controllers\UploadController;

Route::middleware(IsLoggedIn::class)->group(function () {
    Route::get('/organizations', [OrganizationController::class, 'getOrganizations'])
        ->name('organizations.get');

    Route::prefix('/{organization}/{repository}')->group(function () {

        Route::get('/{organization}/{repository}/items/{type}', [ItemController::class, 'index'])
            ->name('organizations.repositories.items.get');

        Route::get('/{organization}/{repository}/contributors', [RepositoryController::class, 'getContributors'])
            ->name('organizations.repositories.contributors.get');

        Route::post('/{organization}/{repository}/item/{number}', [ItemController::class, 'update'])
            ->name('organizations.repositories.item.update');

        Route::get('/{organization}/{repository}/item/{number}', [ItemController::class, 'show'])
            ->name('organizations.repositories.item.show');

        Route::post('/{organization}/{repository}/item/{number}/comment/{comment_id}', [BaseCommentController::class, 'updateItem'])
            ->name('organizations.repositories.item.comment');

        Route::post('/{organization}/{repository}/item/{number}/review/{review_id}', [BaseCommentController::class, 'updateReview'])
            ->name('organizations.repositories.item.review');

        Route::post('/{organization}/{repository}/item/{number}/review/comment/{comment_id}', [BaseCommentController::class, 'updateReviewComment'])
            ->name('organizations.repositories.item.review.comment');

        Route::get('/{organization}/{repository}/item/{number}/files', [ItemController::class, 'getFiles'])
            ->name('organizations.repositories.item.files');

            Route::get('/{organization}/{repository}/branches/pr/notices', [RepositoryController::class, 'getBranchesForPRNotices'])
            ->name('organizations.repositories.branches.pr.notices');

        Route::get('/{organization}/{repository}/pr/metadata', [PullRequestController::class, 'metadata'])
            ->name('organizations.repositories.pr.metadata');

        Route::post('/{organization}/{repository}/pr/create', [PullRequestController::class, 'create'])
            ->name('organizations.repositories.pr.create');
    });

    // Media uploads (images/videos) from markdown editor
    Route::post('/uploads', [UploadController::class, 'store'])->name('uploads.store');
});

Route::any('incoming_hook', [IncomingWebhookController::class, 'index'])
    ->name('api.webhook');
