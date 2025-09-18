<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            // Ensure all columns exist and have correct types
            if (!Schema::hasColumn('issues', 'repository_id')) {
                $table->unsignedBigInteger('repository_id')->nullable();
            }
            if (!Schema::hasColumn('issues', 'opened_by_id')) {
                $table->unsignedBigInteger('opened_by_id')->nullable();
            }
            if (!Schema::hasColumn('issues', 'github_id')) {
                $table->unsignedBigInteger('github_id')->unique();
            }
            if (!Schema::hasColumn('issues', 'number')) {
                $table->unsignedBigInteger('number');
            }
            if (!Schema::hasColumn('issues', 'title')) {
                $table->string('title')->nullable();
            }
            if (!Schema::hasColumn('issues', 'body')) {
                $table->text('body')->nullable();
            }
            if (!Schema::hasColumn('issues', 'last_updated')) {
                $table->datetime('last_updated')->nullable();
            }
            if (!Schema::hasColumn('issues', 'state')) {
                $table->string('state')->default('open');
            }
            if (!Schema::hasColumn('issues', 'labels')) {
                $table->json('labels')->nullable();
            }
            if (!Schema::hasColumn('issues', 'assignees')) {
                $table->json('assignees')->nullable();
            }

            // Ensure timestamps exist
            if (!Schema::hasColumn('issues', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('issues', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop columns in production
    }
};
