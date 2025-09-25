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
        Schema::create('pull_request_assignees', function (Blueprint $table) {
            $table->unsignedBigInteger('pull_request_id');
            $table->unsignedBigInteger('github_user_id');
            $table->timestamps();

            $table->primary(['pull_request_id', 'github_user_id']);

            $table->foreign('pull_request_id')->references('github_id')->on('pull_requests')->onDelete('cascade');
            $table->foreign('github_user_id')->references('github_id')->on('github_users')->onDelete('cascade');

            $table->index('pull_request_id');
            $table->index('github_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pull_request_assignees');
    }
};
