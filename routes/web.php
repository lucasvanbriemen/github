<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\IsLoggedIn;
use App\Http\Controllers\DashboardController;
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
    '/organization/{organization}/{repository}/issues',
    [RepositoryController::class, 'issues']
)
->where('file_path', '.*')
->middleware(IsLoggedIn::class)
->name('repository.issues.show');

Route::get(
    '/organization/{organization}/{repository}/issue/{issue}',
    [RepositoryController::class, 'issue']
)
->where('file_path', '.*')
->middleware(IsLoggedIn::class)
->name('repository.issue.show');
