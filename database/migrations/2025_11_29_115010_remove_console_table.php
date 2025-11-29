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
        // Remove the console table
        Schema::dropIfExists('console');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
