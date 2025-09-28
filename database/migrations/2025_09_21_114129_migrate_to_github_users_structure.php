<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate repository_users to github_users
        $repositoryUsers = DB::table('repository_users')
            ->select('user_id', 'name', 'avatar_url')
            ->distinct()
            ->get();

        foreach ($repositoryUsers as $user) {
            DB::table('github_users')->updateOrInsert(
                ['github_id' => $user->user_id],
                [
                    'login' => $user->name ?? '',
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                    'type' => 'User',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Migrate issue assignees from JSON to pivot table
        $issues = DB::table('issues')
            ->whereNotNull('assignees')
            ->get(['github_id', 'assignees']);

        foreach ($issues as $issue) {
            $assignees = json_decode($issue->assignees, true);

            if (!empty($assignees) && is_array($assignees)) {
                foreach ($assignees as $assigneeId) {
                    // First ensure the github user exists
                    $repoUser = DB::table('repository_users')
                        ->where('user_id', $assigneeId)
                        ->first();

                    if ($repoUser) {
                        DB::table('github_users')->updateOrInsert(
                            ['github_id' => $assigneeId],
                            [
                                'login' => $repoUser->name ?? '',
                                'name' => $repoUser->name,
                                'avatar_url' => $repoUser->avatar_url,
                                'type' => 'User',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );

                        // Add to pivot table
                        DB::table('issue_assignees')->insertOrIgnore([
                            'issue_id' => $issue->github_id,
                            'github_user_id' => $assigneeId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear the pivot table
        DB::table('issue_assignees')->truncate();

        // Note: We don't remove github_users as they might be used elsewhere
    }
};