<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\RepositoryUserController;
use App\Models\SystemInfo;
use App\Models\Console;

Artisan::command("organizations:update", function () {
    $this->info("Updating organizations from GitHub API...");
    
    try {
        OrganizationController::updateOrganizations();
        $this->info("Organizations updated successfully!");
        Console::create(["command" => "organizations:update", "successful" => true, "executed_at" => now()]);
    } catch (\Exception $e) {
        $this->error("Failed to update organizations: " . $e->getMessage());
        Console::create(["command" => "organizations:update", "successful" => false, "executed_at" => now()]);
    }
})->purpose("Update organizations from GitHub API");

Artisan::command("repositories:update", function () {
    $this->info("Updating repositories from GitHub API...");

    try {
        RepositoryController::updateRepositories();
        $this->info("Organizations updated successfully!");
        Console::create(["command" => "repositories:update", "successful" => true, "executed_at" => now()]);
    } catch (\Exception $e) {
        $this->error("Failed to update repositories: " . $e->getMessage());
        Console::create(["command" => "repositories:update", "successful" => false, "executed_at" => now()]);
    }
})->purpose("Update repositories from GitHub API");

Artisan::command("issues:update", function () {
    $this->info("Updating issues from GitHub API...");

    try {
        IssueController::updateIssues();
        $this->info("Issues updated successfully!");
        Console::create(["command" => "issues:update", "successful" => true, "executed_at" => now()]);
    } catch (\Exception $e) {
        $this->error("Failed to update issues: " . $e->getMessage());
        Console::create(["command" => "issues:update", "successful" => false, "executed_at" => now()]);
    }
});

Artisan::command("system:remove_expired", function () {
    $this->info("Removing expired system info from the database...");
    SystemInfo::removeExpired();
    $this->info("Expired system info removed successfully!");
    Console::create(["command" => "system:remove_expired", "successful" => true, "executed_at" => now()]);
})->purpose("Remove expired system info from the database");

Artisan::command("repository_users:update", function () {
    $this->info("Updating repository users from GitHub API...");

    try {
        RepositoryUserController::updateRepositoryUsers();
        $this->info("Repository users updated successfully!");
        Console::create(["command" => "repository_users:update", "successful" => true, "executed_at" => now()]);
    } catch (\Exception $e) {
        $this->error("Failed to update repository users: " . $e->getMessage());
        Console::create(["command" => "repository_users:update", "successful" => false, "executed_at" => now()]);
    }
})->purpose("Update repository users from GitHub API");

Artisan::command("timelines:fetch", function () {
    $this->info("Fetching issue timelines from GitHub API...");

    try {
        IssueController::updateTimelines();
        $this->info("Timeline fetch completed successfully!");
        Console::create(["command" => "timelines:fetch", "successful" => true, "executed_at" => now()]);
    } catch (\Exception $e) {
        $this->error("Failed to fetch timelines: " . $e->getMessage());
        Console::create(["command" => "timelines:fetch", "successful" => false, "executed_at" => now()]);
    }
})->purpose("Fetch issue timelines from GitHub API");

// Schedule the command to run every other day at 2 AM
Schedule::command("organizations:update")->cron("0 2 */2 * *");

// Schedule the command to run every hour (we need this so issues and PRs are updated more frequently)
Schedule::command("repositories:update")->cron("0 * * * *");
Schedule::command("issues:update")->cron("0 * * * *");

// Schedule the command to run daily at 1 AM to update repository users
Schedule::command("repository_users:update")->dailyAt("1:00");

// Schedule the command to run daily at 3 AM to clean up expired system info
Schedule::command("system:remove_expired")->dailyAt("3:30");

// Schedule timeline fetching every hour at :30
Schedule::command("timelines:fetch")->cron("30 * * * *");