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
        // Ensure organizations has github_id and make it the primary key
        if (!Schema::hasColumn('organizations', 'github_id')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->unsignedBigInteger('github_id')->nullable()->after('id');
            });
        }

        // Try to migrate existing organization_id values into github_id when numeric
        DB::statement("UPDATE organizations SET github_id = CAST(organization_id AS UNSIGNED) WHERE github_id IS NULL AND organization_id REGEXP '^[0-9]+$'");

        // Drop existing primary key if present
        $orgPk = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_NAME = 'organizations' AND CONSTRAINT_TYPE = 'PRIMARY KEY'");
        if (!empty($orgPk)) {
            DB::statement('ALTER TABLE organizations DROP PRIMARY KEY');
        }

        // Ensure github_id is non-nullable and set as primary key
        DB::statement('ALTER TABLE organizations MODIFY github_id BIGINT UNSIGNED NOT NULL');
        Schema::table('organizations', function (Blueprint $table) {
            $table->primary('github_id');
        });

        // Ensure repositories.organization_id is BIGINT UNSIGNED to match organizations.github_id
        if (Schema::hasColumn('repositories', 'organization_id')) {
            DB::statement('ALTER TABLE repositories MODIFY organization_id BIGINT UNSIGNED NULL');
        }

        // Step 1: Update repository_users table to use composite primary key
        if (Schema::hasColumn('repository_users', 'id')) {
            // Remove auto_increment from id column first
            DB::statement('ALTER TABLE repository_users MODIFY id BIGINT UNSIGNED NOT NULL');

            Schema::table('repository_users', function (Blueprint $table) {
                $table->dropPrimary();
            });

            Schema::table('repository_users', function (Blueprint $table) {
                // Create composite primary key
                $table->primary(['repository_id', 'user_id']);
                // Drop id column
                $table->dropColumn('id');
            });
        }

        // Step 2: Add foreign key constraints (check if they don't already exist)
        // Check for existing foreign keys before adding
        $repoForeignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                                       WHERE TABLE_NAME = 'repositories'
                                       AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                                       AND CONSTRAINT_NAME LIKE '%organization%'");

        if (empty($repoForeignKeys)) {
            Schema::table('repositories', function (Blueprint $table) {
                $table->foreign('organization_id')->references('github_id')->on('organizations')->onDelete('set null');
            });
        }

        $issueForeignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                                        WHERE TABLE_NAME = 'issues'
                                        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                                        AND CONSTRAINT_NAME LIKE '%repository%'");

        if (empty($issueForeignKeys)) {
            Schema::table('issues', function (Blueprint $table) {
                $table->foreign('repository_id')->references('github_id')->on('repositories')->onDelete('cascade');
            });
        }

        $userForeignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                                       WHERE TABLE_NAME = 'repository_users'
                                       AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                                       AND CONSTRAINT_NAME LIKE '%repository%'");

        if (empty($userForeignKeys)) {
            Schema::table('repository_users', function (Blueprint $table) {
                $table->foreign('repository_id')->references('github_id')->on('repositories')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign key constraints
        Schema::table('repositories', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->dropForeign(['repository_id']);
        });

        Schema::table('repository_users', function (Blueprint $table) {
            $table->dropForeign(['repository_id']);
        });
    }
};
