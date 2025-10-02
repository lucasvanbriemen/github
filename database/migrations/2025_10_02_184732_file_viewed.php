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
        Schema::create('file_viewed', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pull_request_id');
            $table->string('pull_request_id');
            $table->timestamps();

            $table->foreign('pull_request_id')->references('id')->on('pull_requests')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
