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
        //
        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('github_id')->nullable();
            $table->unsignedBigInteger('repository_id');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('github_users')->nullOnDelete();
            $table->foreign('repository_id')->references('id')->on('repositories')->onDelete('cascade');
            $table->string('status')->default('draft');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('releases');
    }
};
