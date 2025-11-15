<?php

use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RepositoryUserController;
use App\Models\Console;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('organizations:update', function () {
    $this->info('Updating organizations from GitHub API...');

    try {
        OrganizationController::updateOrganizations();
        Console::create(['command' => 'organizations:update', 'successful' => true, 'executed_at' => now()]);
    } catch (\Exception $e) {
        Console::create(['command' => 'organizations:update', 'successful' => false, 'executed_at' => now()]);
    }
})->purpose('Update organizations from GitHub API');

Artisan::command('system:remove_expired', function () {
    $this->info('Removing expired system info from the database...');
    $this->info('Expired system info removed successfully!');
    Console::create(['command' => 'system:remove_expired', 'successful' => true, 'executed_at' => now()]);
})->purpose('Remove expired system info from the database');

Artisan::command('repository_users:update', function () {
    $this->info('Updating repository users from GitHub API...');

    try {
        RepositoryUserController::updateRepositoryUsers();
        $this->info('Repository users updated successfully!');
        Console::create(['command' => 'repository_users:update', 'successful' => true, 'executed_at' => now()]);
    } catch (\Exception $e) {
        $this->error('Failed to update repository users: '.$e->getMessage());
        Console::create(['command' => 'repository_users:update', 'successful' => false, 'executed_at' => now()]);
    }
})->purpose('Update repository users from GitHub API');

// Schedule the command to run every other day at 2 AM
Schedule::command('organizations:update')->cron('0 2 */2 * *');

// Schedule the command to run daily at 1 AM to update repository users
Schedule::command('repository_users:update')->dailyAt('1:00');

// Schedule the command to run daily at 3 AM to clean up expired system info
Schedule::command('system:remove_expired')->dailyAt('3:30');
