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
        if (!Schema::hasTable('requested_reviewers')) {
            Schema::create('requested_reviewers', function (Blueprint $table) {
                $table->id();
                $table->timestamps();

                $table->unsignedBigInteger('pull_request_id')->unsigned();
                $table->unsignedBigInteger('user_id');
                $table->enum("state", ["pending", "approved", "changes_requested", "commented"])->default("pending");

                $table->foreign('pull_request_id')->references('id')->on('pull_requests')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requested_reviewers');
    }
};
