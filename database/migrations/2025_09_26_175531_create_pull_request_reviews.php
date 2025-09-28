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
        Schema::create('pull_request_reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->timestamps();
            $table->unsignedBigInteger('pull_request_id');
            $table->unsignedBigInteger('user_id');
            $table->longText('body')->nullable();
            $table->enum("state", ["approved", "changes_requested", "commented"])->default("commented");

            $table->foreign('pull_request_id')->references('github_id')->on('pull_requests')->onDelete('cascade');
            $table->foreign('user_id')->references('github_id')->on('github_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pull_request_reviews');
    }
};
