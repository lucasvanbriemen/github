<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RepositoryController;
use App\Http\Middleware\IsLoggedIn;

Route::patch("/organizations", [OrganizationController::class, "updateOrganizations"])->middleware(IsLoggedIn::class)->name("organizations.update");
Route::patch("/organizations/repositories", [RepositoryController::class, "updateRepositories"])->middleware(IsLoggedIn::class)->name("organizations.repositories.update");
Route::get("/organization/{organization}/{repository}/tree/{file_path?}", [RepositoryController::class, "show_file_tree"])->middleware(IsLoggedIn::class)->name("organizations.repositories.show");