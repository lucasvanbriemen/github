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
        Schema::create('commits', function (Blueprint $table) {
            $table->string('sha')->primary();
            $table->timestamps();

            $table->unsignedBigInteger('repository_github_id');
            $table->foreign('repository_github_id')
                ->references('github_id')
                ->on('repositories')
                ->onDelete('cascade');

            $table->unsignedBigInteger('branch_id');
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('cascade');

            $table->unsignedBigInteger('github_user_id');
            $table->foreign('github_user_id')
                ->references('github_id')
                ->on('github_users')
                ->onDelete('cascade');

            $table->text('message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commits');
    }
};
