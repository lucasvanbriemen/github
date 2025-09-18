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
            // Remove the old foreign key constraint if it exists
            try {
                $table->dropForeign(['repository_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist, ignore error
            }

            // Add new foreign key constraint referencing github_id
            $table->foreign('repository_id')->references('github_id')->on('repositories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            // Remove the github_id foreign key
            $table->dropForeign(['repository_id']);

            // Restore original foreign key to repositories.id
            $table->foreign('repository_id')->references('id')->on('repositories')->onDelete('cascade');
        });
    }
};
