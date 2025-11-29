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
        // Remove the viewed_files table
        Schema::dropIfExists('viewed_files');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
