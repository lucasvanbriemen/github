<?php

use App\Http\Controllers\IncomingWebhookController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RepositoryController;
use App\Http\Middleware\IsLoggedIn;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemCommentController;

Route::middleware(IsLoggedIn::class)->group(function () {
    Route::get('/org', [OrganizationController::class, 'getOrganizations'])
        ->name('organizations.get');

    Route::get('/org/{organization}/repo/{repository}/items/{type}', [ItemController::class, 'index'])
        ->name('organizations.repositories.items.get');

    Route::get('/org/{organization}/repo/{repository}/contributors', [RepositoryController::class, 'getContributors'])
        ->name('organizations.repositories.contributors.get');

    Route::get('/org/{organization}/repo/{repository}/item/{number}', [ItemController::class, 'show'])
        ->name('organizations.repositories.item.show');

    Route::post('/org/{organization}/repo/{repository}/item/{number}/comment/{comment_id}', [ItemCommentController::class, 'updateItem'])
        ->name('organizations.repositories.item.comment');

    Route::post('/org/{organization}/repo/{repository}/item/{number}/review/{review_id}', [ItemCommentController::class, 'updateReview'])
        ->name('organizations.repositories.item.review');

    Route::post('/org/{organization}/repo/{repository}/item/{number}/review/comment/{comment_id}', [ItemCommentController::class, 'updateReviewComment'])
        ->name('organizations.repositories.item.review.comment');

    Route::get('/org/{organization}/repo/{repository}/item/{number}/files', [ItemController::class, 'getFiles'])
        ->name('organizations.repositories.item.files');
    
    Route::get('/org/{organization}/repo/{repository}/branches/pr/notices', [RepositoryController::class, 'getBranchesForPRNotices'])
        ->name('organizations.repositories.branches.pr.notices');

});

Route::any('incoming_hook', [IncomingWebhookController::class, 'index'])
    ->name('api.webhook');