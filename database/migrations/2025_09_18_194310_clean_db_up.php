<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        // Truncate issue table
        Schema::table('issues', function (Blueprint $table) {
            DB::table('issues')->truncate();
        });

        // From issues table remove opendy_by opend_by_image, resopsary_full_name
        Schema::table('issues', function (Blueprint $table) {
            if (Schema::hasColumn('issues', 'opened_by')) {
                $table->dropColumn('opened_by');
            }
            if (Schema::hasColumn('issues', 'opened_by_image')) {
                $table->dropColumn('opened_by_image');
            }
            if (Schema::hasColumn('issues', 'repository_full_name')) {
                $table->dropColumn('repository_full_name');
            }

            // Add repository_id
            if (!Schema::hasColumn('issues', 'repository_id')) {
                $table->uuid('repository_id')->nullable()->after('id');
                $table->foreign('repository_id')->references('id')->on('repositories')->onDelete('cascade');
            }

            // Add the repository_users_id column
            if (!Schema::hasColumn('issues', 'repository_users_id')) {
                $table->unsignedBigInteger('repository_users_id')->nullable()->after('repository_id');
                $table->foreign('repository_users_id')->references('id')->on('repository_users')->onDelete('set null');
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
