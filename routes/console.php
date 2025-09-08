<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RepositoryController;

Artisan::command("organizations:update", function () {
    $this->info("Updating organizations from GitHub API...");
    
    try {
        OrganizationController::updateOrganizations();
        $this->info("Organizations updated successfully!");
    } catch (\Exception $e) {
        $this->error("Failed to update organizations: " . $e->getMessage());
    }
})->purpose("Update organizations from GitHub API");

Artisan::command("repositories:update", function () {
    $this->info("Updating repositories from GitHub API...");

    try {
        RepositoryController::updateRepositories();
        $this->info("Organizations updated successfully!");
    } catch (\Exception $e) {
        $this->error("Failed to update repositories: " . $e->getMessage());
    }
})->purpose("Update repositories from GitHub API");

// Schedule the command to run every other day at 2 AM
Schedule::command("organizations:update")->cron("0 2 */2 * *");

// Schedule the command to run every day at 2 AM
Schedule::command("repositories:update")->cron("0 2 * * *");
