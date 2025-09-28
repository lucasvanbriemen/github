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
        // remove the repository_users_id column from issues table
        Schema::table('issues', function (Blueprint $table) {
            if (Schema::hasColumn('issues', 'repository_users_id')) {
                $table->dropColumn('repository_users_id');
            }
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
