<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pull_request_reviews', function (Blueprint $table) {
            // Add the base_comment_id column to link to base_comments
            $table->unsignedBigInteger('base_comment_id')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('pull_request_reviews', function (Blueprint $table) {
            //
        });
    }
};
