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
        Schema::table('pull_request_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('in_reply_to_id')->nullable()->after('body')->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('pull_request_comments', function (Blueprint $table) {
            $table->dropColumn('in_reply_to_id');
        });
    }
};
