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
        Schema::table('pull_request_comments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);          // drop FK for user_id
            $table->dropForeign(['pull_request_id']);  // drop FK for pull_request_id
            $table->dropColumn(['user_id', 'body', 'resolved', 'pull_request_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_comments_pr5', function (Blueprint $table) {
            //
        });
    }
};
