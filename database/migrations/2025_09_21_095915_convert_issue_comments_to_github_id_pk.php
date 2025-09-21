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
        // Convert issue_comments to use github_id as primary key
        Schema::table('issue_comments', function (Blueprint $table) {
            // First remove auto_increment from id column
            DB::statement('ALTER TABLE issue_comments MODIFY id BIGINT UNSIGNED NOT NULL');
        });

        Schema::table('issue_comments', function (Blueprint $table) {
            // Drop the old primary key
            $table->dropPrimary();
        });

        Schema::table('issue_comments', function (Blueprint $table) {
            // Make github_id the primary key
            $table->primary('github_id');
            // Drop the old id column
            $table->dropColumn('id');
        });

        // Add foreign key constraint for issue_github_id
        Schema::table('issue_comments', function (Blueprint $table) {
            $table->foreign('issue_github_id')->references('github_id')->on('issues')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign key
        Schema::table('issue_comments', function (Blueprint $table) {
            $table->dropForeign(['issue_github_id']);
        });

        // This migration changes primary key structure
        throw new \Exception('This migration cannot be fully reversed. Please restore from backup.');
    }
};