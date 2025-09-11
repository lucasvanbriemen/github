<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\IsLoggedIn;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RepositoryController;


Route::get("/", [DashboardController::class, "index"])->middleware(IsLoggedIn::class)->name("dashboard");
Route::get("/organization/{organization}", [OrganizationController::class, "show"])->middleware(IsLoggedIn::class)->name("organization.show");

// Repository root
Route::get(
    '/organization/{organization}/{repository}',
    [RepositoryController::class, 'show']
)
->middleware(IsLoggedIn::class)
->name('repository.show.root');

// Repository subpaths (tree)
Route::get(
    '/organization/{organization}/{repository}/tree/{file_path?}',
    [RepositoryController::class, 'show']
)
->where('file_path', '.*')
->middleware(IsLoggedIn::class)
->name('repository.show');
