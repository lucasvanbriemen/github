<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Item;
use App\Services\ImportanceScoreService;

class RecalculateItemScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:recalculate-scores {--only-assigned : Only recalculate for items assigned to the current user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate importance scores for all items';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = Item::query();

        if ($this->option('only-assigned')) {
            $this->info('Recalculating scores for assigned items only...');
            // We'll filter in PHP since the isCurrentlyAssignedToUser check requires the relationship
        } else {
            $this->info('Recalculating scores for all items...');
        }

        $items = $query->get();
        $total = $items->count();
        $updated = 0;

        $this->withProgressBar($items, function ($item) use (&$updated) {
            if ($this->option('only-assigned') && !$item->isCurrentlyAssignedToUser()) {
                return;
            }

            ImportanceScoreService::updateItemScore($item);
            $updated++;
        });

        $this->newLine();
        $this->info("✓ Recalculated scores for {$updated} items (out of {$total} total)");

        return self::SUCCESS;
    }
}
