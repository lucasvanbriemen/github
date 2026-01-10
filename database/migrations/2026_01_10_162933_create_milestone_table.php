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
        Schema::create('milestones', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->timestamps();
            $table->foreignId('repository_id')
                ->constrained('repositories')
                ->cascadeOnDelete();
            $table->string('state');
            $table->string('title');
            $table->dateTime('due_on')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milestones');
    }
};
