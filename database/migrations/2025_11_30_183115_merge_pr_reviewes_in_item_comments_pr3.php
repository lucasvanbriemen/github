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
        Schema::table('pull_request_reviews', function (Blueprint $table) {
            // Drop FKs individually to match default names
            $table->dropForeign(['user_id']);
            $table->dropForeign(['pull_request_id']);

            // Remove columns that moved to base_comments
            $table->dropColumn(['body', 'user_id', 'resolved', 'pull_request_id']);

            // Add FK from base_comment_id to base_comments(id)
            $table->foreign('base_comment_id')->references('id')->on('base_comments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pull_request_reviews', function (Blueprint $table) {
            //
        });
    }
};
