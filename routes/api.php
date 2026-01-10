<?php

use App\Http\Controllers\IncomingWebhookController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\ProjectController;
use App\Http\Middleware\IsLoggedIn;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PullRequestController;
use App\Http\Controllers\BaseCommentController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\NotificationController;

Route::middleware(IsLoggedIn::class)->group(function () {
    Route::get('/organizations', [OrganizationController::class, 'index'])
        ->name('organizations');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications');

    Route::get('/notification/{id}', [NotificationController::class, 'show'])
        ->name('notification.show');

    Route::post('/notifications/{id}/complete', [NotificationController::class, 'complete'])
        ->name('notifications.complete');

    Route::name('organizations.repositories.')->prefix('/{organization}/{repository}')->group(function () {
        Route::get('/projects', [ProjectController::class, 'index'])
            ->name('projects');

        Route::get('/projects/{number}', [ProjectController::class, 'show'])
            ->name('project.show');

        Route::post('/item/add-to-project', [ProjectController::class, 'addItemToProject'])
            ->name('project.item.add');

        Route::post('/item/update-project-status', [ProjectController::class, 'updateItemProjectStatus'])
            ->name('project.item.update');

        Route::post('/item/remove-from-project', [ProjectController::class, 'removeItemFromProject'])
            ->name('project.item.remove');

        Route::get('/metadata', [RepositoryController::class, 'metadata'])
            ->name('metadata');

        Route::get('/contributors', [RepositoryController::class, 'getContributors'])
            ->name('contributors');

        Route::get('/items/{type}', [ItemController::class, 'index'])
            ->name('items');

        Route::post('/item/{number}/update-labels', [ItemController::class, 'updateLabels'])
            ->name('item.label.update');

        Route::post('/item/{number}/update-assignees', [ItemController::class, 'updateAssignees'])
            ->name('item.assignees.update');

        Route::post('/item/{number}', [ItemController::class, 'update'])
            ->name('item.update');

        Route::get('/item/{number}', [ItemController::class, 'show'])
            ->name('item.show');

        Route::get('/item/{number}/linked', [ItemController::class, 'getLinkedItems'])
            ->name('item.linked.get');

        Route::get('/item/{number}/linkable', [ItemController::class, 'searchLinkableItems'])
            ->name('item.linkable.search');

        Route::post('/item/{number}/link/bulk', [ItemController::class, 'createBulkLinks'])
            ->name('item.link.bulk.create');

        Route::post('/item/{number}/link/bulk/remove', [ItemController::class, 'removeBulkLinks'])
            ->name('item.link.bulk.remove');

        Route::post('/item/{number}/comment/{comment_id}', [BaseCommentController::class, 'updateItem'])
            ->name('item.comment');

        Route::post('item/{number}/comment', [BaseCommentController::class, 'createItemComment'])
            ->name('item.comment.create');

        Route::post('/item/{number}/review/comments', [BaseCommentController::class, 'createPRComment'])
            ->name('item.review.comments.create');

        Route::get('/pr/{number}/files', [PullRequestController::class, 'getFiles'])
            ->name('pr.files');

        Route::get('/branches/pr/notices', [RepositoryController::class, 'getBranchesForPRNotices'])
            ->name('branches.pr.notices');

        Route::post('/pr/create', [PullRequestController::class, 'create'])
            ->name('pr.create');

        Route::post('/pr/{number}', [PullRequestController::class, 'update'])
            ->name('pr.update');

        Route::post('/pr/{number}/merge', [PullRequestController::class, 'merge'])
            ->name('pr.merge');

        Route::post('/pr/{number}/reviewers', [PullRequestController::class, 'requestReviewers'])
            ->name('pr.add.reviewers');

        Route::post('/pr/{number}/review', [PullRequestController::class, 'submitReview'])
            ->name('pr.review.submit');

        Route::post('/issue/create', [ItemController::class, 'create'])
            ->name('issues.create');
    });

    // Media uploads (images/videos) from markdown editor
    Route::post('/uploads', [UploadController::class, 'store'])->name('uploads.store');
});

Route::any('incoming_hook', [IncomingWebhookController::class, 'index'])
    ->name('api.webhook');
