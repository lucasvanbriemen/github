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
        Schema::dropIfExists('pull_request_review_comments');
        Schema::dropIfExists('pull_request_reviews');
        Schema::dropIfExists('pull_request_comments');
        Schema::dropIfExists('pull_request_linked_issues');
        Schema::dropIfExists('pull_request_assignees');
        Schema::dropIfExists('pull_request_reviewers');
        Schema::dropIfExists('pull_requests');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // These tables were removed - no need to recreate them
    }
};
