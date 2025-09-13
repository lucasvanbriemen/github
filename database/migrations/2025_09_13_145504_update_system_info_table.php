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
        Schema::table('system_info', function (Blueprint $table) {
            //
            $table->dropColumn('api_count');

            $table->string('api_url')->nullable();
            $table->time("expires_at")
                ->description("Then the API call doenst contrubute to the limit anymore");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_info', function (Blueprint $table) {
            //
        });
    }
};
