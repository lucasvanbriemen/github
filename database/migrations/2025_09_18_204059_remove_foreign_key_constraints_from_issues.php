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
        Schema::table('issues', function (Blueprint $table) {
            // Drop the foreign key constraint that's causing issues
            $table->dropForeign('issues_repository_users_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            // Recreate the foreign key if needed
            $table->foreign('opened_by_id')->references('id')->on('repository_users')->onDelete('set null');
        });
    }
};
