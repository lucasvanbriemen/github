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
        //
        // Add github_id to repositories
        Schema::table('repositories', function (Blueprint $table) {
            if (!Schema::hasColumn('repositories', 'github_id')) {
                $table->unsignedBigInteger('github_id')->nullable()->after('name')->unique();
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
