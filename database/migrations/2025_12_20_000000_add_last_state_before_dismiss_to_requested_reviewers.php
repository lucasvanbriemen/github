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
        Schema::table('requested_reviewers', function (Blueprint $table) {
            $table->enum('last_state_before_dismiss', ['approved', 'changes_requested', 'commented'])->nullable()->after('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requested_reviewers', function (Blueprint $table) {
            $table->dropColumn('last_state_before_dismiss');
        });
    }
};
