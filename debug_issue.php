<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Looking for issue 14690...\n";

$issue = App\Models\Issue::where('github_id', 14690)->first();

if ($issue) {
    echo "Issue found: " . $issue->title . "\n";
    echo "opened_by_id: " . $issue->opened_by_id . "\n";

    echo "\nChecking openedBy relationship:\n";
    $openedBy = $issue->openedBy;
    if ($openedBy) {
        echo "Found openedBy user: " . $openedBy->name . "\n";
        echo "Avatar URL: " . $openedBy->avatar_url . "\n";
    } else {
        echo "openedBy is null!\n";

        // Check if the user exists in github_users table
        echo "\nChecking if user exists in github_users table...\n";
        $user = App\Models\GithubUser::where('github_id', $issue->opened_by_id)->first();
        if ($user) {
            echo "User found in github_users: " . $user->name . "\n";
        } else {
            echo "User NOT found in github_users table with github_id: " . $issue->opened_by_id . "\n";
        }
    }
} else {
    echo "Issue not found\n";
}