<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Merge pull_request_assignees into issue_assignees (which now points to items table)
        if (Schema::hasTable('pull_request_assignees')) {
            // Copy data from pull_request_assignees to issue_assignees
            DB::statement(<<<SQL
                INSERT IGNORE INTO issue_assignees (issue_id, user_id)
                SELECT pull_request_id, user_id
                FROM pull_request_assignees
            SQL);

            // Drop the pull_request_assignees table
            Schema::drop('pull_request_assignees');
        }

        // Optionally rename issue_assignees to item_assignees for clarity
        // (keeping as issue_assignees for backwards compatibility)

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate pull_request_assignees table
        if (!Schema::hasTable('pull_request_assignees')) {
            Schema::create('pull_request_assignees', function (Blueprint $table) {
                $table->unsignedBigInteger('pull_request_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();

                $table->primary(['pull_request_id', 'user_id']);
                $table->foreign('pull_request_id')->references('id')->on('items')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('github_users')->onDelete('cascade');
            });

            // Copy PR assignees back from issue_assignees
            DB::statement(<<<SQL
                INSERT INTO pull_request_assignees (pull_request_id, user_id)
                SELECT ia.issue_id, ia.user_id
                FROM issue_assignees ia
                INNER JOIN items i ON ia.issue_id = i.id
                WHERE i.type = 'pull_request'
            SQL);

            // Remove PR assignees from issue_assignees
            DB::statement(<<<SQL
                DELETE ia FROM issue_assignees ia
                INNER JOIN items i ON ia.issue_id = i.id
                WHERE i.type = 'pull_request'
            SQL);
        }
    }
};
