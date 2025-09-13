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
        Schema::table('issues', function (Blueprint $table) {
            //
            $table->string('opened_by')->nullable();
            $table->string('opened_by_image')->nullable();

            $table->json('labels')->nullable();
            $table->json('assignees')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            //

            $table->dropColumn('opened_by');
            $table->dropColumn('opened_by_image');

            $table->dropColumn('labels');
            $table->dropColumn('assignees');
        });
    }
};
