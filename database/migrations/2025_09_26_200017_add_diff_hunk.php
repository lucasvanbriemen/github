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
        // add the line_start and line_end columns to pull_request_comments table
        Schema::table('pull_request_comments', function (Blueprint $table) {
            $table->integer('line_start')->nullable()->after('diff_hunk');
            $table->integer('line_end')->nullable()->after('line_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
