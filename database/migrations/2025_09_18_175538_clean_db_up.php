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
        // From the issue table drop state_reason and closed_by columns
        Schema::table('issues', function (Blueprint $table) {
            if (Schema::hasColumn('issues', 'state_reason')) {
                $table->dropColumn('state_reason');
                $table->dropColumn('closed_by');
            }
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
