<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\IsLoggedIn;
use App\Http\Controllers\DashboardController;

Route::get("/", [DashboardController::class, "index"])->middleware(IsLoggedIn::class)->name("dashboard");
