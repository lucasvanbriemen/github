<?php

use App\Http\Controllers\IncomingWebhookController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\PullRequestController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RepositoryController;
use App\Http\Middleware\IsLoggedIn;
use Illuminate\Support\Facades\Route;

Route::patch('/organizations', [OrganizationController::class, 'updateOrganizations'])->middleware(IsLoggedIn::class)->name('organizations.update');
Route::get('/organization/{organization}/{repository}/tree/{file_path?}', [RepositoryController::class, 'show_file_tree'])
    ->middleware(IsLoggedIn::class)
    ->name('api.repositories.show')
    ->where('file_path', '.*');

Route::get('/organization/{organization}/{repository}/issues', [IssueController::class, 'getIssues'])
    ->middleware(IsLoggedIn::class)
    ->name('api.repositories.issues');

Route::patch('/organization/{organization}/{repository}/issues/{issue}/comments/{comment}/resolve', [IssueController::class, 'resolveComment'])
    ->middleware(IsLoggedIn::class)
    ->name('api.repositories.issues.comment.resolve');

Route::patch('/organization/{organization}/{repository}/issues/{issue}/comments/{comment}/unresolve', [IssueController::class, 'unresolveComment'])
    ->middleware(IsLoggedIn::class)
    ->name('api.repositories.issues.comment.unresolve');

Route::get('/organization/{organization}/{repository}/pull_requests', [PullRequestController::class, 'getPullRequests'])
    ->middleware(IsLoggedIn::class)
    ->name('api.repositories.pull_requests');

Route::any('incoming_hook', [IncomingWebhookController::class, 'index'])->name('api.webhook');
