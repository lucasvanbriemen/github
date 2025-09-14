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
        Schema::create('repositories_users', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->uuid('repository_id');
            $table->foreign('repository_id')->references('id')->on('repositories')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->string("name");
            $table->string("avatar_url")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repositories_users');
    }
};
