<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Item;
use App\Services\ImportanceScoreService;

class RecalculateItemScores extends Command
{
    protected $signature = 'items:recalculate-scores';

    protected $description = 'Recalculate importance scores for all items';

    public function handle(): int
    {
        $query = Item::query();

        $this->info('Recalculating scores for all items...');

        $items = $query->get();
        $total = $items->count();
        $updated = 0;

        $this->withProgressBar($items, function ($item) use (&$updated) {
            ImportanceScoreService::updateItemScore($item);
            $updated++;
        });

        $this->newLine();
        $this->info("✓ Recalculated scores for {$updated} items (out of {$total} total)");

        return self::SUCCESS;
    }
}
