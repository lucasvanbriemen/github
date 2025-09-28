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
        Schema::table('repositories', function (Blueprint $table) {
            $table->index(['name', 'organization_id']);
            $table->index('organization_id');
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->index(['repository_id', 'number']);
            $table->index(['repository_id', 'state']);
            $table->index('repository_id');
            $table->index('last_updated');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repositories', function (Blueprint $table) {
            $table->dropIndex(['name', 'organization_id']);
            $table->dropIndex(['organization_id']);
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->dropIndex(['repository_id', 'number']);
            $table->dropIndex(['repository_id', 'state']);
            $table->dropIndex(['repository_id']);
            $table->dropIndex(['last_updated']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
    }
};
