<?php

use App\Http\Controllers\IncomingWebhookController;
use App\Http\Controllers\IssueController;
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
        ->name('organizations.repositories.get.contributors');

    Route::get('/org/{organization}/repo/{repository}/item/{number}', [ItemController::class, 'show'])
        ->name('organizations.repositories.item.show');

    Route::post('/org/{organization}/repo/{repository}/item/{number}/comment/{comment_id}', [ItemCommentController::class, 'updateItem'])
        ->name('organizations.repositories.item.comment');

    Route::post('/org/{organization}/repo/{repository}/item/{number}/review/{review_id}', [ItemCommentController::class, 'updateReview'])
        ->name('organizations.repositories.item.review');

    Route::post('/org/{organization}/repo/{repository}/item/{number}/review/comment/{comment_id}', [ItemCommentController::class, 'updateReviewComment'])
        ->name('organizations.repositories.item.review.comment');

    // Route::prefix('organization/{organization}/{repository}')->group(function () {
    //     Route::get('tree/{file_path?}', [RepositoryController::class, 'show_file_tree'])
    //         ->where('file_path', '.*')
    //         ->name('api.repositories.show');

    //     Route::prefix('issues')->group(function () {
    //         Route::get('/', [IssueController::class, 'getIssues'])
    //             ->name('api.repositories.issues');

    //         Route::post('/', [IssueController::class, 'createIssue'])
    //             ->name('api.repositories.issues.create');

    //         Route::prefix('{issue}')->group(function () {
    //             Route::get('linked_pull_requests', [IssueController::class, 'getLinkedPullRequestsHtml'])
    //                 ->name('api.repositories.issues.issue');

    //             Route::post('/comments', [PullRequestController::class, 'addComment'])
    //                 ->name('api.repositories.issues.comments.add');

    //             Route::prefix('comments/{comment}')->group(function () {
    //                 Route::patch('resolve', [IssueController::class, 'resolveComment'])
    //                     ->name('api.repositories.issues.comment.resolve');

    //                 Route::patch('unresolve', [IssueController::class, 'unresolveComment'])
    //                     ->name('api.repositories.issues.comment.unresolve');
    //             });
    //         });
    //     });

    //     Route::prefix('pull_requests')->group(function () {
    //         Route::get('/', [PullRequestController::class, 'getPullRequests'])
    //             ->name('api.repositories.pull_requests');

    //         Route::prefix('{pullRequest}')->group(function () {

    //             Route::get('linked_issues', [PullRequestController::class, 'getLinkedIssuesHtml'])
    //                 ->name('api.repositories.pull_requests.pull_request');

    //             Route::patch('edit', [PullRequestController::class, 'updatePullRequest'])
    //                 ->name('api.repositories.pull_requests.edit');

    //             Route::put('merge', [PullRequestController::class, 'mergePullRequest'])
    //                 ->name('api.repositories.pull_requests.merge');

    //             Route::patch('close', [PullRequestController::class, 'closePullRequest'])
    //                 ->name('api.repositories.pull_requests.close');

    //             Route::prefix('comments')->group(function () {
    //                 Route::post('/', [PullRequestController::class, 'addPRComment'])
    //                     ->name('api.repositories.pull_requests.comments.add');
                    
    //                 Route::prefix('{comment}')->group(function () {
    //                     Route::patch('resolve', [PullRequestController::class, 'resolveComment'])
    //                         ->name('api.repositories.pull_requests.comment.resolve');
    //                     Route::patch('unresolve', [PullRequestController::class, 'unresolveComment'])
    //                         ->name('api.repositories.pull_requests.comment.unresolve');
    //                 });
    //             });

    //             Route::prefix('review/{comment}')->group(function () {
    //                 Route::patch('resolve', [PullRequestController::class, 'resolveReviewComment'])
    //                     ->name('api.repositories.pull_requests.review.resolve');
    //                 Route::patch('unresolve', [PullRequestController::class, 'unresolveReviewComment'])
    //                     ->name('api.repositories.pull_requests.review.unresolve');
    //             });

    //             Route::get('files/viewed', [PullRequestController::class, 'fileViewed'])
    //                 ->name('api.repositories.pull_requests.files.viewed');

    //             Route::get('files/not_viewed', [PullRequestController::class, 'fileNotViewed'])
    //                 ->name('api.repositories.pull_requests.files.not_viewed');
    //         });
    //     });
    // });
});

Route::any('incoming_hook', [IncomingWebhookController::class, 'index'])
    ->name('api.webhook');
    
// Route::any('check_end_point', function () {
//     return response()->json(['redirect' => true, 'url' => route('dashboard')]);
// })->name('api.endpoint.check');
