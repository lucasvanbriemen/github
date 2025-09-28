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
        // Add bool reloved to pull_request_comments and pull_request_reviews
        Schema::table('pull_request_comments', function (Blueprint $table) {
            $table->boolean('resolved')->default(false)->after('body');
        });

        Schema::table('pull_request_reviews', function (Blueprint $table) {
            $table->boolean('resolved')->default(false)->after('body');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('pull_request_comments', function (Blueprint $table) {
            $table->dropColumn('resolved');
        });

        Schema::table('pull_request_reviews', function (Blueprint $table) {
            $table->dropColumn('resolved');
        });
    }
};
