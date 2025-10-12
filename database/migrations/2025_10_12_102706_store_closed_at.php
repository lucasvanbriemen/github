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
        // in the pull_requests table, we add a closed_at datetime column that can be null
        Schema::table('pull_requests', function (Blueprint $table) {
            $table->dateTime('closed_at')->nullable();

            // turn the state column into an enum with values 'open', 'closed', 'merged'
            $table->enum('state', ['open', 'closed', 'merged'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('pull_requests', function (Blueprint $table) {
            $table->dropColumn('closed_at');

            // turn the state column back to a string
            $table->string('state')->change();
        });
    }
};
