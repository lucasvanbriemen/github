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
        // Pull Requests
        Schema::create('pull_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('github_id')->primary();
            $table->timestamps();

            $table->unsignedBigInteger('repository_id');
            $table->foreign('repository_id')->references('github_id')->on('repositories')->onDelete('cascade');

            $table->unsignedBigInteger('number');
            $table->string('title');
            $table->longText('body')->nullable();
            $table->string('state')->default('open'); // open, closed, merged, draft
            $table->boolean('draft')->default(false);
            $table->unsignedBigInteger('author_id')->nullable();
            $table->foreign('author_id')->references('github_id')->on('github_users')->onDelete('set null');
            $table->string('source_branch');
            $table->string('target_branch');
            $table->json('labels')->nullable();
        });

        // Requested reviewers (pivot)
        Schema::create('pull_request_reviewers', function (Blueprint $table) {
            $table->unsignedBigInteger('pull_request_github_id');
            $table->unsignedBigInteger('github_user_id');
            $table->timestamps();

            $table->primary(['pull_request_github_id', 'github_user_id']);
            $table->foreign('pull_request_github_id')->references('github_id')->on('pull_requests')->onDelete('cascade');
            $table->foreign('github_user_id')->references('github_id')->on('github_users')->onDelete('cascade');
            $table->index('pull_request_github_id');
            $table->index('github_user_id');
        });

        // Assignees (pivot)
        Schema::create('pull_request_assignees', function (Blueprint $table) {
            $table->unsignedBigInteger('pull_request_github_id');
            $table->unsignedBigInteger('github_user_id');
            $table->timestamps();

            $table->primary(['pull_request_github_id', 'github_user_id']);
            $table->foreign('pull_request_github_id')->references('github_id')->on('pull_requests')->onDelete('cascade');
            $table->foreign('github_user_id')->references('github_id')->on('github_users')->onDelete('cascade');
            $table->index('pull_request_github_id');
            $table->index('github_user_id');
        });

        // PR Reviews
        Schema::create('pull_request_reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('github_id')->primary();
            $table->timestamps();

            $table->unsignedBigInteger('pull_request_github_id');
            $table->foreign('pull_request_github_id')->references('github_id')->on('pull_requests')->onDelete('cascade');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('github_id')->on('github_users')->onDelete('cascade');

            $table->string('state'); // approved, changes_requested, commented, dismissed, pending
            $table->text('body')->nullable();
            $table->timestamp('submitted_at')->nullable();
        });

        // PR Review Comments (diff comments)
        Schema::create('pull_request_review_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('github_id')->primary();
            $table->timestamps();

            $table->unsignedBigInteger('pull_request_github_id');
            $table->foreign('pull_request_github_id')->references('github_id')->on('pull_requests')->onDelete('cascade');

            $table->unsignedBigInteger('pull_request_review_github_id')->nullable();
            $table->foreign('pull_request_review_github_id')->references('github_id')->on('pull_request_reviews')->onDelete('set null');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('github_id')->on('github_users')->onDelete('cascade');

            $table->text('body')->nullable();
            $table->string('path')->nullable();
            $table->text('diff_hunk')->nullable();
            $table->string('commit_id', 64)->nullable();
            $table->unsignedBigInteger('in_reply_to_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pull_request_review_comments');
        Schema::dropIfExists('pull_request_reviews');
        Schema::dropIfExists('pull_request_assignees');
        Schema::dropIfExists('pull_request_reviewers');
        Schema::dropIfExists('pull_requests');
    }
};
