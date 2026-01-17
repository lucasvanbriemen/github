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

        $this->info('Recalculating scores for all items');

        $items = $query->get();

        $this->withProgressBar($items, function ($item) {
            ImportanceScoreService::updateItemScore($item);
        });

        $this->newLine();
        $this->info("Recalculated scores for {$items->count()} items");

        return self::SUCCESS;
    }
}
