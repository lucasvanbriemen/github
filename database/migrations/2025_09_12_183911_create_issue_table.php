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
        Schema::create('issue', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('github_id')->unique();
            $table->string('repository_full_name');
            $table->unsignedBigInteger('number');
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->datetime('last_updated')->nullable();
            $table->string('state')->default('open');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue');
    }
};
