<?php

use App\GithubConfig;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\RepositoryUserController;
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

Artisan::command('items:resync-labels', function () {
    RepositoryController::resyncItemLabels();
})->purpose('Rebuild the item_labels pivot from GitHub for all items');

// Schedule the command to run every other day at 2 AM
Schedule::command('organizations:update')->cron('0 2 */2 * *');

// Schedule the command to run daily at 1 AM to update repository users
Schedule::command('repository_users:update')->dailyAt('1:00');

// Schedule the command to run daily at 1 AM to update labels
Schedule::command('labels:update')->dailyAt('01:00');

Artisan::command('notifications:overview', function () {
    NotificationController::sendOverview();
})->purpose('Send daily overview email of unread notifications');

// Schedule the overview email at start of working days
$time = GithubConfig::NOTIFICATION_DIGEST_TIMES[strtolower(date('l'))];
Schedule::command('notifications:overview')->dailyAt($time);
