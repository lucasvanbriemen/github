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
        // On the pull requests table, add a new column called base_branch to store the base branch name
        Schema::table('pull_requests', function (Blueprint $table) {
            $table->string('head_branch')->nullable()->default(null);
            $table->string('base_branch')->nullable()->after('head_branch')->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('pull_requests', function (Blueprint $table) {
            $table->dropColumn('base_branch');
            $table->dropColumn('head_branch');
        });
    }
};
