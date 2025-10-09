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
        // Step 1: Drop all foreign key constraints that reference github_id
        $this->dropForeignKeyConstraints();

        // Step 2: Rename github_id columns to id in all tables
        $this->renameColumns();

        // Step 3: Clean up orphaned records
        $this->cleanOrphanedRecords();

        // Step 4: Recreate foreign key constraints with new column names
        $this->recreateForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Drop all foreign key constraints
        $this->dropForeignKeyConstraintsDown();

        // Step 2: Rename id back to github_id
        $this->renameColumnsDown();

        // Step 3: Recreate original foreign key constraints
        $this->recreateForeignKeyConstraintsDown();
    }

    private function dropForeignKeyConstraints(): void
    {
        // Drop foreign keys in pull_request_reviews
        if (Schema::hasTable('pull_request_reviews')) {
            $constraints = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                                       WHERE TABLE_NAME = 'pull_request_reviews'
                                       AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE pull_request_reviews DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
            }
        }

        // Drop foreign keys in pull_requests
        if (Schema::hasTable('pull_requests')) {
            $constraints = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                                       WHERE TABLE_NAME = 'pull_requests'
                                       AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE pull_requests DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
            }
        }

        // Drop foreign keys in issue_comments
        if (Schema::hasTable('issue_comments')) {
            $constraints = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                                       WHERE TABLE_NAME = 'issue_comments'
                                       AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE issue_comments DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
            }
        }

        // Drop foreign keys in branches
        if (Schema::hasTable('branches')) {
            $constraints = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                                       WHERE TABLE_NAME = 'branches'
                                       AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE branches DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
            }
        }

        // Drop foreign keys in commits
        if (Schema::hasTable('commits')) {
            $constraints = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                                       WHERE TABLE_NAME = 'commits'
                                       AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE commits DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
            }
        }

        // Drop foreign keys in repositories
        if (Schema::hasTable('repositories')) {
            $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                                       WHERE TABLE_NAME = 'repositories'
                                       AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                                       AND CONSTRAINT_NAME LIKE '%organization%'");
            if (!empty($foreignKeys)) {
                Schema::table('repositories', function (Blueprint $table) {
                    $table->dropForeign(['organization_id']);
                });
            }
        }

        // Drop foreign keys in issues
        if (Schema::hasTable('issues')) {
            $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                                       WHERE TABLE_NAME = 'issues'
                                       AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                                       AND CONSTRAINT_NAME LIKE '%repository%'");
            if (!empty($foreignKeys)) {
                Schema::table('issues', function (Blueprint $table) {
                    $table->dropForeign(['repository_id']);
                });
            }
        }

        // Drop foreign keys in repository_users
        if (Schema::hasTable('repository_users')) {
            $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                                       WHERE TABLE_NAME = 'repository_users'
                                       AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                                       AND CONSTRAINT_NAME LIKE '%repository%'");
            if (!empty($foreignKeys)) {
                Schema::table('repository_users', function (Blueprint $table) {
                    $table->dropForeign(['repository_id']);
                });
            }
        }

        // Add more foreign key drops for other tables as needed
        $this->dropAdditionalForeignKeys();
    }

    private function dropAdditionalForeignKeys(): void
    {
        // Drop foreign keys in pull_request_assignees
        if (Schema::hasTable('pull_request_assignees')) {
            $constraints = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                                       WHERE TABLE_NAME = 'pull_request_assignees'
                                       AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE pull_request_assignees DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
            }
        }

        // Drop foreign keys in issue_assignees
        if (Schema::hasTable('issue_assignees')) {
            $constraints = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                                       WHERE TABLE_NAME = 'issue_assignees'
                                       AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE issue_assignees DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
            }
        }

        // Drop foreign keys in requested_reviewers
        if (Schema::hasTable('requested_reviewers')) {
            $constraints = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                                       WHERE TABLE_NAME = 'requested_reviewers'
                                       AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE requested_reviewers DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
            }
        }

        // Drop foreign keys in pull_request_comments
        if (Schema::hasTable('pull_request_comments')) {
            $constraints = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                                       WHERE TABLE_NAME = 'pull_request_comments'
                                       AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE pull_request_comments DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
            }
        }
    }

    private function renameColumns(): void
    {
        // Rename in pull_requests (primary key) - this is the main table that needs renaming
        if (Schema::hasColumn('pull_requests', 'github_id') && !Schema::hasColumn('pull_requests', 'id')) {
            // Check if there's a primary key first
            $primaryKey = DB::select("SHOW KEYS FROM pull_requests WHERE Key_name = 'PRIMARY'");
            if (!empty($primaryKey)) {
                DB::statement('ALTER TABLE pull_requests DROP PRIMARY KEY');
            }
            DB::statement('ALTER TABLE pull_requests CHANGE github_id id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE pull_requests ADD PRIMARY KEY (id)');
        }

        // Rename foreign key columns
        if (Schema::hasColumn('issue_comments', 'issue_github_id')) {
            DB::statement('ALTER TABLE issue_comments CHANGE issue_github_id issue_id BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasColumn('branches', 'repository_github_id')) {
            DB::statement('ALTER TABLE branches CHANGE repository_github_id repository_id BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasColumn('commits', 'repository_github_id')) {
            DB::statement('ALTER TABLE commits CHANGE repository_github_id repository_id BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasColumn('commits', 'github_user_id')) {
            DB::statement('ALTER TABLE commits CHANGE github_user_id user_id BIGINT UNSIGNED NOT NULL');
        }

        // Rename in pull_request_assignees
        if (Schema::hasColumn('pull_request_assignees', 'github_user_id')) {
            DB::statement('ALTER TABLE pull_request_assignees CHANGE github_user_id user_id BIGINT UNSIGNED NOT NULL');
        }

        // Rename in issue_assignees
        if (Schema::hasColumn('issue_assignees', 'github_user_id')) {
            DB::statement('ALTER TABLE issue_assignees CHANGE github_user_id user_id BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasColumn('issue_assignees', 'issue_github_id')) {
            DB::statement('ALTER TABLE issue_assignees CHANGE issue_github_id issue_id BIGINT UNSIGNED NOT NULL');
        }

        // Note: requested_reviewers doesn't have github_user_id in the actual database
    }

    private function cleanOrphanedRecords(): void
    {
        // Clean up orphaned issue_comments
        DB::statement('DELETE ic FROM issue_comments ic LEFT JOIN issues i ON ic.issue_id = i.id WHERE i.id IS NULL');
        DB::statement('DELETE ic FROM issue_comments ic LEFT JOIN github_users gu ON ic.user_id = gu.id WHERE gu.id IS NULL');

        // Clean up other orphaned records if they exist
        DB::statement('DELETE pa FROM pull_request_assignees pa LEFT JOIN pull_requests pr ON pa.pull_request_id = pr.id WHERE pr.id IS NULL');
        DB::statement('DELETE pa FROM pull_request_assignees pa LEFT JOIN github_users gu ON pa.user_id = gu.id WHERE gu.id IS NULL');

        DB::statement('DELETE ia FROM issue_assignees ia LEFT JOIN issues i ON ia.issue_id = i.id WHERE i.id IS NULL');
        DB::statement('DELETE ia FROM issue_assignees ia LEFT JOIN github_users gu ON ia.user_id = gu.id WHERE gu.id IS NULL');

        DB::statement('DELETE rr FROM requested_reviewers rr LEFT JOIN pull_requests pr ON rr.pull_request_id = pr.id WHERE pr.id IS NULL');

        DB::statement('DELETE prc FROM pull_request_comments prc LEFT JOIN pull_requests pr ON prc.pull_request_id = pr.id WHERE pr.id IS NULL');
        DB::statement('DELETE prc FROM pull_request_comments prc LEFT JOIN github_users gu ON prc.user_id = gu.id WHERE gu.id IS NULL');

        DB::statement('DELETE prr FROM pull_request_reviews prr LEFT JOIN pull_requests pr ON prr.pull_request_id = pr.id WHERE pr.id IS NULL');
        DB::statement('DELETE prr FROM pull_request_reviews prr LEFT JOIN github_users gu ON prr.user_id = gu.id WHERE gu.id IS NULL');

        DB::statement('DELETE b FROM branches b LEFT JOIN repositories r ON b.repository_id = r.id WHERE r.id IS NULL');

        DB::statement('DELETE c FROM commits c LEFT JOIN repositories r ON c.repository_id = r.id WHERE r.id IS NULL');
        DB::statement('DELETE c FROM commits c LEFT JOIN github_users gu ON c.user_id = gu.id WHERE gu.id IS NULL');

        // Clean up pull_requests with orphaned references
        DB::statement('DELETE pr FROM pull_requests pr LEFT JOIN repositories r ON pr.repository_id = r.id WHERE r.id IS NULL');
        DB::statement('DELETE pr FROM pull_requests pr LEFT JOIN github_users gu ON pr.opened_by_id = gu.id WHERE gu.id IS NULL');
    }

    private function recreateForeignKeyConstraints(): void
    {
        // Recreate foreign keys in pull_request_reviews
        if (Schema::hasTable('pull_request_reviews')) {
            Schema::table('pull_request_reviews', function (Blueprint $table) {
                $table->foreign('pull_request_id')->references('id')->on('pull_requests')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('github_users')->onDelete('cascade');
            });
        }

        // Recreate foreign keys in pull_requests
        if (Schema::hasTable('pull_requests')) {
            Schema::table('pull_requests', function (Blueprint $table) {
                $table->foreign('repository_id')->references('id')->on('repositories')->onDelete('cascade');
                $table->foreign('opened_by_id')->references('id')->on('github_users')->onDelete('cascade');
            });
        }

        // Recreate foreign keys in issue_comments
        if (Schema::hasTable('issue_comments')) {
            Schema::table('issue_comments', function (Blueprint $table) {
                $table->foreign('issue_id')->references('id')->on('issues')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('github_users')->onDelete('cascade');
            });
        }

        // Recreate foreign keys in branches
        if (Schema::hasTable('branches')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->foreign('repository_id')->references('id')->on('repositories')->onDelete('cascade');
            });
        }

        // Recreate foreign keys in commits
        if (Schema::hasTable('commits')) {
            Schema::table('commits', function (Blueprint $table) {
                $table->foreign('repository_id')->references('id')->on('repositories')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('github_users')->onDelete('cascade');
            });
        }

        // Recreate foreign keys in repositories
        if (Schema::hasTable('repositories')) {
            Schema::table('repositories', function (Blueprint $table) {
                $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
            });
        }

        // Recreate foreign keys in issues
        if (Schema::hasTable('issues')) {
            Schema::table('issues', function (Blueprint $table) {
                $table->foreign('repository_id')->references('id')->on('repositories')->onDelete('cascade');
            });
        }

        // Recreate foreign keys in repository_users
        if (Schema::hasTable('repository_users')) {
            Schema::table('repository_users', function (Blueprint $table) {
                $table->foreign('repository_id')->references('id')->on('repositories')->onDelete('cascade');
            });
        }

        // Recreate additional foreign keys
        $this->recreateAdditionalForeignKeys();
    }

    private function recreateAdditionalForeignKeys(): void
    {
        // Recreate foreign keys in pull_request_assignees
        if (Schema::hasTable('pull_request_assignees')) {
            Schema::table('pull_request_assignees', function (Blueprint $table) {
                $table->foreign('pull_request_id')->references('id')->on('pull_requests')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('github_users')->onDelete('cascade');
            });
        }

        // Recreate foreign keys in issue_assignees
        if (Schema::hasTable('issue_assignees')) {
            Schema::table('issue_assignees', function (Blueprint $table) {
                $table->foreign('issue_id')->references('id')->on('issues')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('github_users')->onDelete('cascade');
            });
        }

        // Recreate foreign keys in requested_reviewers
        if (Schema::hasTable('requested_reviewers')) {
            Schema::table('requested_reviewers', function (Blueprint $table) {
                $table->foreign('pull_request_id')->references('id')->on('pull_requests')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('github_users')->onDelete('cascade');
            });
        }

        // Recreate foreign keys in pull_request_comments
        if (Schema::hasTable('pull_request_comments')) {
            Schema::table('pull_request_comments', function (Blueprint $table) {
                $table->foreign('pull_request_id')->references('id')->on('pull_requests')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('github_users')->onDelete('cascade');
            });
        }
    }

    // Down methods for rollback
    private function dropForeignKeyConstraintsDown(): void
    {
        // Same as up but for rollback
        $this->dropForeignKeyConstraints();
    }

    private function renameColumnsDown(): void
    {
        // Reverse: Rename id back to github_id in pull_requests
        if (Schema::hasColumn('pull_requests', 'id') && !Schema::hasColumn('pull_requests', 'github_id')) {
            DB::statement('ALTER TABLE pull_requests DROP PRIMARY KEY');
            DB::statement('ALTER TABLE pull_requests CHANGE id github_id BIGINT UNSIGNED NOT NULL');
        }

        // Reverse foreign key renames
        if (Schema::hasColumn('issue_comments', 'issue_id')) {
            DB::statement('ALTER TABLE issue_comments CHANGE issue_id issue_github_id BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasColumn('branches', 'repository_id')) {
            DB::statement('ALTER TABLE branches CHANGE repository_id repository_github_id BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasColumn('commits', 'repository_id')) {
            DB::statement('ALTER TABLE commits CHANGE repository_id repository_github_id BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasColumn('commits', 'user_id')) {
            DB::statement('ALTER TABLE commits CHANGE user_id github_user_id BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasColumn('pull_request_assignees', 'user_id')) {
            DB::statement('ALTER TABLE pull_request_assignees CHANGE user_id github_user_id BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasColumn('issue_assignees', 'user_id')) {
            DB::statement('ALTER TABLE issue_assignees CHANGE user_id github_user_id BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasColumn('issue_assignees', 'issue_id')) {
            DB::statement('ALTER TABLE issue_assignees CHANGE issue_id issue_github_id BIGINT UNSIGNED NOT NULL');
        }
    }

    private function recreateForeignKeyConstraintsDown(): void
    {
        // Recreate with original column names
        if (Schema::hasTable('pull_request_reviews')) {
            Schema::table('pull_request_reviews', function (Blueprint $table) {
                $table->foreign('pull_request_id')->references('github_id')->on('pull_requests')->onDelete('cascade');
                $table->foreign('user_id')->references('github_id')->on('github_users')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('pull_requests')) {
            Schema::table('pull_requests', function (Blueprint $table) {
                $table->foreign('repository_id')->references('github_id')->on('repositories')->onDelete('cascade');
                $table->foreign('opened_by_id')->references('github_id')->on('github_users')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('issue_comments')) {
            Schema::table('issue_comments', function (Blueprint $table) {
                $table->foreign('issue_github_id')->references('github_id')->on('issues')->onDelete('cascade');
                $table->foreign('user_id')->references('github_id')->on('github_users')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('branches')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->foreign('repository_github_id')->references('github_id')->on('repositories')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('commits')) {
            Schema::table('commits', function (Blueprint $table) {
                $table->foreign('repository_github_id')->references('github_id')->on('repositories')->onDelete('cascade');
                $table->foreign('github_user_id')->references('github_id')->on('github_users')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('repositories')) {
            Schema::table('repositories', function (Blueprint $table) {
                $table->foreign('organization_id')->references('github_id')->on('organizations')->onDelete('set null');
            });
        }

        if (Schema::hasTable('issues')) {
            Schema::table('issues', function (Blueprint $table) {
                $table->foreign('repository_id')->references('github_id')->on('repositories')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('repository_users')) {
            Schema::table('repository_users', function (Blueprint $table) {
                $table->foreign('repository_id')->references('github_id')->on('repositories')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('pull_request_assignees')) {
            Schema::table('pull_request_assignees', function (Blueprint $table) {
                $table->foreign('pull_request_id')->references('github_id')->on('pull_requests')->onDelete('cascade');
                $table->foreign('github_user_id')->references('github_id')->on('github_users')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('issue_assignees')) {
            Schema::table('issue_assignees', function (Blueprint $table) {
                $table->foreign('issue_github_id')->references('github_id')->on('issues')->onDelete('cascade');
                $table->foreign('github_user_id')->references('github_id')->on('github_users')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('requested_reviewers')) {
            Schema::table('requested_reviewers', function (Blueprint $table) {
                $table->foreign('pull_request_id')->references('github_id')->on('pull_requests')->onDelete('cascade');
                $table->foreign('github_user_id')->references('github_id')->on('github_users')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('pull_request_comments')) {
            Schema::table('pull_request_comments', function (Blueprint $table) {
                $table->foreign('pull_request_id')->references('github_id')->on('pull_requests')->onDelete('cascade');
                $table->foreign('user_id')->references('github_id')->on('github_users')->onDelete('cascade');
            });
        }
    }
};
