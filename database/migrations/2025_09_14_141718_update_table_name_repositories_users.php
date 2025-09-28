<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename the table from 'repositories_users' to 'repository_users'
        Schema::rename('repositories_users', 'repository_users');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
