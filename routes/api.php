<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrganizationController;
use App\Http\Middleware\IsLoggedIn;

Route::patch("/organizations", [OrganizationController::class, "updateOrganizations"])->middleware(IsLoggedIn::class)->name("organizations.update");
