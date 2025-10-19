<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1) Create unified items table (table-per-type: Issues and PRs share base fields)
        if (!Schema::hasTable('items')) {
            Schema::create('items', function (Blueprint $table) {
                // GitHub-provided id, non-incrementing
                $table->unsignedBigInteger('id')->primary();
                $table->timestamps();

                $table->unsignedBigInteger('repository_id');
                $table->unsignedBigInteger('number');
                $table->string('title')->nullable();
                $table->text('body')->nullable();
                // Using enum to support PR's merged state; issues will only use open/closed
                $table->enum('state', ['open', 'closed', 'merged'])->default('open');
                $table->json('labels')->nullable()->default('[]');
                $table->unsignedBigInteger('opened_by_id')->nullable();
                $table->enum('type', ['issue', 'pull_request']);

                $table->index(['repository_id', 'number']);

                $table->foreign('repository_id')->references('id')->on('repositories')->onDelete('cascade');
                $table->foreign('opened_by_id')->references('id')->on('github_users')->onDelete('set null');
            });
        }

        // 2) Backfill data from issues -> items
        if (Schema::hasTable('issues')) {
            DB::statement(<<<SQL
                INSERT INTO items (id, repository_id, number, title, body, state, labels, opened_by_id, created_at, updated_at, type)
                SELECT i.id, i.repository_id, i.number, i.title, i.body, i.state, i.labels, i.opened_by_id, i.created_at, i.updated_at, 'issue'
                FROM issues i
                WHERE NOT EXISTS (
                    SELECT 1 FROM items it WHERE it.id = i.id
                )
            SQL);
        }

        // 3) Backfill data from pull_requests -> items (for common/base fields)
        if (Schema::hasTable('pull_requests') && Schema::hasColumn('pull_requests', 'repository_id')) {
            DB::statement(<<<SQL
                INSERT INTO items (id, repository_id, number, title, body, state, labels, opened_by_id, created_at, updated_at, type)
                SELECT pr.id, pr.repository_id, pr.number, pr.title, pr.body, pr.state, pr.labels, pr.opened_by_id, pr.created_at, pr.updated_at, 'pull_request'
                FROM pull_requests pr
                WHERE NOT EXISTS (
                    SELECT 1 FROM items it WHERE it.id = pr.id
                )
            SQL);
        }

        // 4) Re-point foreign keys that previously referenced issues -> items
        if (Schema::hasTable('issue_comments')) {
            try {
                Schema::table('issue_comments', function (Blueprint $table) {
                    $table->dropForeign(['issue_id']);
                });
            } catch (\Throwable $e) {
                // Foreign key might not exist or already dropped
            }

            try {
                Schema::table('issue_comments', function (Blueprint $table) {
                    $table->foreign('issue_id')->references('id')->on('items')->onDelete('cascade');
                });
            } catch (\Throwable $e) {
                // Foreign key might already exist
            }
        }

        if (Schema::hasTable('issue_assignees')) {
            try {
                Schema::table('issue_assignees', function (Blueprint $table) {
                    $table->dropForeign(['issue_id']);
                });
            } catch (\Throwable $e) {
                // Foreign key might not exist or already dropped
            }

            try {
                Schema::table('issue_assignees', function (Blueprint $table) {
                    $table->foreign('issue_id')->references('id')->on('items')->onDelete('cascade');
                });
            } catch (\Throwable $e) {
                // Foreign key might already exist
            }
        }

        // Note: pull_request_assignees merging is handled in a separate migration
        // (2025_10_19_120340_merge_pull_request_assignees_into_issue_assignees)

        // 5) Link pull_requests to items by shared primary key and drop duplicated/base columns
        if (Schema::hasTable('pull_requests')) {
            // Drop foreign keys only if the columns still exist
            if (Schema::hasColumn('pull_requests', 'repository_id')) {
                try {
                    Schema::table('pull_requests', function (Blueprint $table) {
                        $table->dropForeign(['repository_id']);
                    });
                } catch (\Throwable $e) {
                    // Foreign key might not exist, continue
                }
            }

            if (Schema::hasColumn('pull_requests', 'opened_by_id')) {
                try {
                    Schema::table('pull_requests', function (Blueprint $table) {
                        $table->dropForeign(['opened_by_id']);
                    });
                } catch (\Throwable $e) {
                    // Foreign key might not exist, continue
                }
            }

            // Add FK from id -> items.id (if not already present)
            try {
                Schema::table('pull_requests', function (Blueprint $table) {
                    $table->foreign('id')->references('id')->on('items')->onDelete('cascade');
                });
            } catch (\Throwable $e) {
                // Foreign key might already exist, continue
            }

            // Now drop duplicated/base columns from pull_requests, keeping PR-specific ones
            Schema::table('pull_requests', function (Blueprint $table) {
                if (Schema::hasColumn('pull_requests', 'repository_id')) { $table->dropColumn('repository_id'); }
                if (Schema::hasColumn('pull_requests', 'number')) { $table->dropColumn('number'); }
                if (Schema::hasColumn('pull_requests', 'title')) { $table->dropColumn('title'); }
                if (Schema::hasColumn('pull_requests', 'body')) { $table->dropColumn('body'); }
                if (Schema::hasColumn('pull_requests', 'state')) { $table->dropColumn('state'); }
                if (Schema::hasColumn('pull_requests', 'labels')) { $table->dropColumn('labels'); }
                if (Schema::hasColumn('pull_requests', 'opened_by_id')) { $table->dropColumn('opened_by_id'); }
            });
        }

        // 6) Drop issues table (data preserved in items)
        if (Schema::hasTable('issues')) {
            Schema::drop('issues');
        }

        Schema::enableForeignKeyConstraints();

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration performs a structural merge and data move.
        // For safety and simplicity, we do not implement automatic rollback.
        // If rollback is required, re-create the dropped columns on pull_requests,
        // re-create issues table, and manually copy data back from items.
    }
};

