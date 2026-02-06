<?php

use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RepositoryUserController;
use App\Http\Controllers\RepositoryController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('organizations:update', function () {
    OrganizationController::updateOrganizations();
})->purpose('Update organizations from GitHub API');

Artisan::command('repository_users:update', function () {
    RepositoryUserController::updateRepositoryUsers();
})->purpose('Update repository users from GitHub API');

Artisan::command('labels:update', function () {
    RepositoryController::updateLabels();
})->purpose('Update labels from GitHub API');


// Schedule the command to run every other day at 2 AM
Schedule::command('organizations:update')->cron('0 2 */2 * *');

// Schedule the command to run daily at 1 AM to update repository users
Schedule::command('repository_users:update')->dailyAt('1:00');

// Schedule the command to run daily at 1 AM to update labels
Schedule::command('labels:update')->dailyAt('01:00');
