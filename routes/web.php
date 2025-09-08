<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\IsLoggedIn;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RepositoryController;


Route::get("/", [DashboardController::class, "index"])->middleware(IsLoggedIn::class)->name("dashboard");
Route::get("/organization/{organization}", [OrganizationController::class, "show"])->middleware(IsLoggedIn::class)->name("organization.show");
Route::get("/organization/{organization}/{repository}", [RepositoryController::class, "show"])->middleware(IsLoggedIn::class)->name("repository.show");
