<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ItemComment;
use App\Models\PullRequestComment;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::table('base_comments', function (Blueprint $table) {
            // Drop primary key constraint on current id
            $table->dropPrimary('PRIMARY'); // or just $table->dropPrimary();

            // Rename id to comment_id
            $table->renameColumn('id', 'comment_id');
        });

        // Add new auto-incrementing primary key
        Schema::table('base_comments', function (Blueprint $table) {
            $table->bigIncrements('id')->first();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pull_request_comments', function (Blueprint $table) {
            //
        });
    }
};
