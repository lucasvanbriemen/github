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
        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('actor_id')->nullable()->after('related_id');
            $table->unsignedBigInteger('repository_id')->nullable()->after('actor_id');
            $table->text('metadata')->nullable()->after('repository_id');

            $table->foreign('actor_id')->references('id')->on('github_users')->onDelete('cascade');
            $table->foreign('repository_id')->references('id')->on('repositories')->onDelete('cascade');
            $table->index(['completed', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['actor_id']);
            $table->dropForeign(['repository_id']);
            $table->dropIndex(['completed', 'created_at']);
            $table->dropColumn(['actor_id', 'repository_id', 'metadata']);
        });
    }
};
