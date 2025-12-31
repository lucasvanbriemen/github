<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Item;
use App\Models\Label;
use App\Models\ItemLabel;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $items = Item::query()->whereNotNull('labels')->get();

        foreach ($items as $item) {
            $labelsData = $item->labels;

            // Handle case where it might be a JSON string (though it should be cast to array)
            if (is_string($labelsData)) {
                $labelsData = json_decode($labelsData, true);
            }

            if (!is_array($labelsData) || empty($labelsData)) {
                continue;
            }

            foreach ($labelsData as $labelData) {
                // Extract the GitHub ID from the label data
                $githubId = $labelData['id'] ?? $labelData['github_id'] ?? null;

                if (!$githubId) {
                    continue; // Skip if we can't find an ID
                }

                // Find the label in the labels table
                $label = Label::where('github_id', $githubId)
                    ->where('repository_id', $item->repository_id)
                    ->first();

                if ($label) {
                    // Create the ItemLabel association (use firstOrCreate to avoid duplicates)
                    ItemLabel::firstOrCreate(
                        [
                            'item_id' => $item->id,
                            'label_id' => $label->id
                        ]
                    );
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
