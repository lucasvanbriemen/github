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
        Schema::table('pull_request_comments', function (Blueprint $table) {
            $table->bigInteger('pull_request_review_id')->nullable();

            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pull_request_comments', function (Blueprint $table) {
            $table->dropColumn('pull_request_review_id');
        });
    }
};
