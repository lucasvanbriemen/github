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
        Schema::create('pull_request', function (Blueprint $table) {
            $table->unsignedBigInteger('github_id')->unique();
            $table->timestamps();
            $table->unsignedBigInteger('repository_id');
            $table->unsignedBigInteger('number');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('state');
            $table->unsignedBigInteger('opened_by_id');

            $table->foreign('repository_id')->references('github_id')->on('repositories')->onDelete('cascade');
            $table->foreign('opened_by_id')->references('github_id')->on('github_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pull_request');
    }
};
