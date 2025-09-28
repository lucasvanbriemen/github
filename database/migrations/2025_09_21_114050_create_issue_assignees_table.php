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
        // Recreate github_users table if it doesn't exist
        if (!Schema::hasTable('github_users')) {
            Schema::create('github_users', function (Blueprint $table) {
                $table->unsignedBigInteger('github_id')->primary();
                $table->string('login');
                $table->string('name')->nullable();
                $table->string('avatar_url')->nullable();
                $table->string('type')->default('User');
                $table->timestamps();

                $table->unique('github_id');
            });
        }

        Schema::create('issue_assignees', function (Blueprint $table) {
            $table->unsignedBigInteger('issue_id');
            $table->unsignedBigInteger('github_user_id');
            $table->timestamps();

            $table->primary(['issue_id', 'github_user_id']);

            $table->foreign('issue_id')->references('github_id')->on('issues')->onDelete('cascade');
            $table->foreign('github_user_id')->references('github_id')->on('github_users')->onDelete('cascade');

            $table->index('issue_id');
            $table->index('github_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_assignees');
    }
};