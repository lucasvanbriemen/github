<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Issue;
use App\Models\PullRequest;
use App\Helpers\ApiHelper;

class CleanupMiscategorizedIssues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:miscategorized-issues {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove issues that are actually pull requests from the issues table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('Running in dry-run mode. No changes will be made.');
        }

        $this->info('Checking for miscategorized issues...');

        $issues = Issue::with('repository.organization')->get();
        $deletedCount = 0;
        $checkedCount = 0;

        foreach ($issues as $issue) {
            $checkedCount++;

            // Check if a PR exists with the same number and repository
            $pr = PullRequest::where('number', $issue->number)
                ->where('repository_id', $issue->repository_id)
                ->first();

            if ($pr) {
                $this->warn("Found issue #{$issue->number} in {$issue->repository->full_name} that is actually PR #{$pr->number}");

                if (!$isDryRun) {
                    $issue->delete();
                    $this->info("  Deleted issue #{$issue->number}");
                } else {
                    $this->info("  Would delete issue #{$issue->number}");
                }

                $deletedCount++;
            }
        }

        $this->newLine();
        $this->info("Checked {$checkedCount} issues.");

        if ($isDryRun) {
            $this->info("Would delete {$deletedCount} miscategorized issues.");
            $this->info('Run without --dry-run to actually delete them.');
        } else {
            $this->info("Deleted {$deletedCount} miscategorized issues.");
        }

        return 0;
    }
}
