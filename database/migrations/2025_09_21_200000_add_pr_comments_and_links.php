<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add node_id to pull_requests for GraphQL lookups
        if (!Schema::hasColumn('pull_requests', 'node_id')) {
            Schema::table('pull_requests', function (Blueprint $table) {
                $table->string('node_id')->nullable()->after('github_id');
            });
        }

        // General PR comments (conversation comments)
        if (!Schema::hasTable('pull_request_comments')) {
            Schema::create('pull_request_comments', function (Blueprint $table) {
                $table->unsignedBigInteger('github_id')->primary();
                $table->timestamps();

                $table->unsignedBigInteger('pull_request_github_id');
                $table->foreign('pull_request_github_id')->references('github_id')->on('pull_requests')->onDelete('cascade');

                $table->unsignedBigInteger('user_id');
                $table->foreign('user_id')->references('github_id')->on('github_users')->onDelete('cascade');

                $table->text('body')->nullable();
            });
        }

        // Linked issues between PR and issues
        if (!Schema::hasTable('pull_request_linked_issues')) {
            Schema::create('pull_request_linked_issues', function (Blueprint $table) {
                $table->unsignedBigInteger('pull_request_github_id');
                $table->unsignedBigInteger('issue_github_id');
                $table->timestamps();

                $table->primary(['pull_request_github_id', 'issue_github_id']);
                $table->foreign('pull_request_github_id')->references('github_id')->on('pull_requests')->onDelete('cascade');
                $table->foreign('issue_github_id')->references('github_id')->on('issues')->onDelete('cascade');
                $table->index('pull_request_github_id');
                $table->index('issue_github_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pull_request_linked_issues');
        Schema::dropIfExists('pull_request_comments');
        if (Schema::hasColumn('pull_requests', 'node_id')) {
            Schema::table('pull_requests', function (Blueprint $table) {
                $table->dropColumn('node_id');
            });
        }
    }
};

