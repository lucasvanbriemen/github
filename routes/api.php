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
    Route::get('/organizations', [OrganizationController::class, 'index'])
        ->name('organizations.get');

    Route::prefix('/{organization}/{repository}')->group(function () {
        Route::get('/items/{type}', [ItemController::class, 'index'])
            ->name('organizations.repositories.items.get');

        Route::get('/contributors', [RepositoryController::class, 'getContributors'])
            ->name('organizations.repositories.contributors.get');

        Route::post('/item/{number}', [ItemController::class, 'update'])
            ->name('organizations.repositories.item.update');

        Route::get('/item/metadata', [ItemController::class, 'metadata'])
            ->name('organizations.repositories.item.metadata');

        Route::get('/item/{number}', [ItemController::class, 'show'])
            ->name('organizations.repositories.item.show');

        Route::get('/item/{number}/linked', [ItemController::class, 'getLinkedItems'])
            ->name('organizations.repositories.item.linked.get');

        Route::post('/item/{number}/comment/{comment_id}', [BaseCommentController::class, 'updateItem'])
            ->name('organizations.repositories.item.comment');

        Route::post('item/{number}/comment', [BaseCommentController::class, 'createItemComment'])
            ->name('organizations.repositories.item.comment.create');

        Route::post('/item/{number}/review/comments', [BaseCommentController::class, 'createPRComment'])
            ->name('organizations.repositories.item.review.comments.create');

        Route::get('/pr/{number}/files', [PullRequestController::class, 'getFiles'])
            ->name('organizations.repositories.pr.files');

        Route::get('/branches/pr/notices', [RepositoryController::class, 'getBranchesForPRNotices'])
            ->name('organizations.repositories.branches.pr.notices');

        Route::post('/pr/create', [PullRequestController::class, 'create'])
            ->name('organizations.repositories.pr.create');

        Route::post('/pr/{number}', [PullRequestController::class, 'update'])
            ->name('organizations.repositories.pr.update');

        Route::post('/pr/{number}/merge', [PullRequestController::class, 'merge'])
            ->name('organizations.repositories.pr.merge');

        Route::post('/pr/{number}/reviewers', [PullRequestController::class, 'requestReviewers'])
            ->name('organizations.repositories.pr.add.reviewers');

        Route::post('/pr/{number}/review', [PullRequestController::class, 'submitReview'])
            ->name('organizations.repositories.pr.review.submit');

        Route::post('/issue/create', [ItemController::class, 'create'])
            ->name('organizations.repositories.issues.create');
    });

    // Media uploads (images/videos) from markdown editor
    Route::post('/uploads', [UploadController::class, 'store'])->name('uploads.store');
});

Route::any('incoming_hook', [IncomingWebhookController::class, 'index'])
    ->name('api.webhook');
