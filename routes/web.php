<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\IsLoggedIn;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RepositoryController;


Route::get("/", [DashboardController::class, "index"])->middleware(IsLoggedIn::class)->name("dashboard");
Route::get("/organization/{organization}", [OrganizationController::class, "show"])->middleware(IsLoggedIn::class)->name("organization.show");

Route::get(
    '/organization/{organization}/{repository}/tree/{file_path?}',
    [RepositoryController::class, 'show']
)
->where('file_path', '.*')
->middleware(IsLoggedIn::class)
->name('repository.show');

Route::get(
    '/organization/{organization}/{repository}',
    // Redirect to repository.show
    function ($organization, $repository) {
        return redirect()->route('repository.show', [
            'organization' => $organization,
            'repository' => $repository,
        ]);
    }
);

Route::get(
    '/organization/{organization}/{repository}/issues',
    [IssueController::class, 'index']
)
->where('file_path', '.*')
->middleware(IsLoggedIn::class)
->name('repository.issues.show');

Route::get(
    '/organization/{organization}/{repository}/issue/{issue}',
    [IssueController::class, 'show']
)
->where('file_path', '.*')
->middleware(IsLoggedIn::class)
->name('repository.issue.show');
