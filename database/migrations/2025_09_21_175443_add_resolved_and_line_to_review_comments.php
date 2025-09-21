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
        Schema::table('pull_request_review_comments', function (Blueprint $table) {
            $table->boolean('resolved')->default(false)->after('diff_hunk');
            $table->unsignedBigInteger('resolved_by')->nullable()->after('resolved');
            $table->timestamp('resolved_at')->nullable()->after('resolved_by');
            $table->integer('original_line')->nullable()->after('path');
            $table->integer('line')->nullable()->after('original_line');
            $table->string('side')->nullable()->after('line');
            $table->integer('start_line')->nullable()->after('side');
            $table->string('start_side')->nullable()->after('start_line');

            $table->foreign('resolved_by')->references('github_id')->on('github_users')->onDelete('set null');
            $table->index(['pull_request_github_id', 'resolved'], 'pr_review_comments_pr_resolved_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pull_request_review_comments', function (Blueprint $table) {
            $table->dropForeign(['resolved_by']);
            $table->dropIndex('pr_review_comments_pr_resolved_idx');

            $table->dropColumn([
                'resolved',
                'resolved_by',
                'resolved_at',
                'original_line',
                'line',
                'side',
                'start_line',
                'start_side'
            ]);
        });
    }
};
