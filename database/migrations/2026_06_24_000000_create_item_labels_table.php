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
        Schema::create('item_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->foreignId('label_id')->constrained('labels')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['item_id', 'label_id']);
        });

        $this->backfillFromItems();

        // The pivot table is now the source of truth for item labels.
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('labels');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->json('labels')->default('[]')->after('state');
        });

        $this->restoreLabelsColumn();

        Schema::dropIfExists('item_labels');
    }

    /**
     * Rebuild the items.labels JSON column from the pivot table so the
     * migration is reversible without data loss.
     */
    private function restoreLabelsColumn(): void
    {
        DB::table('items')->orderBy('id')->chunkById(500, function ($items) {
            foreach ($items as $item) {
                $labels = DB::table('item_labels')
                    ->join('labels', 'labels.id', '=', 'item_labels.label_id')
                    ->where('item_labels.item_id', $item->id)
                    ->get(['labels.github_id as id', 'labels.name', 'labels.color', 'labels.description'])
                    ->map(fn ($l) => (array) $l)
                    ->all();

                DB::table('items')
                    ->where('id', $item->id)
                    ->update(['labels' => json_encode($labels)]);
            }
        });
    }

    /**
     * Populate item_labels from the labels JSON stored on each item.
     */
    private function backfillFromItems(): void
    {
        // Cache labels per repository so each item lookup avoids extra queries.
        // $cache[$repositoryId] = ['byGithubId' => [...], 'byName' => [...]]
        $cache = [];
        $now = now();

        DB::table('items')->orderBy('id')->chunkById(500, function ($items) use (&$cache, $now) {
            $rows = [];

            foreach ($items as $item) {
                $labels = json_decode($item->labels ?? '[]', true);

                if (! is_array($labels) || empty($labels)) {
                    continue;
                }

                $lookup = $this->labelsForRepository($item->repository_id, $cache);

                foreach ($labels as $label) {
                    if (! is_array($label)) {
                        continue;
                    }

                    $githubId = $label['id'] ?? null;
                    $name = $label['name'] ?? null;

                    $labelId = null;
                    if ($githubId !== null && isset($lookup['byGithubId'][$githubId])) {
                        $labelId = $lookup['byGithubId'][$githubId];
                    } elseif ($name !== null && isset($lookup['byName'][$name])) {
                        $labelId = $lookup['byName'][$name];
                    }

                    // Label not synced into the labels table yet — create it so we
                    // don't lose the association that exists on the item.
                    if ($labelId === null && $name !== null) {
                        $labelId = DB::table('labels')->insertGetId([
                            'github_id' => $githubId ?? 0,
                            'repository_id' => $item->repository_id,
                            'name' => $name,
                            'color' => $label['color'] ?? '000000',
                            'description' => $label['description'] ?? null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                        // Keep the cache consistent for subsequent items.
                        $cache[$item->repository_id]['byName'][$name] = $labelId;
                        if ($githubId !== null) {
                            $cache[$item->repository_id]['byGithubId'][$githubId] = $labelId;
                        }
                    }

                    if ($labelId === null) {
                        continue;
                    }

                    $rows[] = [
                        'item_id' => $item->id,
                        'label_id' => $labelId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (! empty($rows)) {
                // insertOrIgnore guards against duplicate (item_id, label_id) pairs
                // that could appear if an item lists the same label twice.
                DB::table('item_labels')->insertOrIgnore($rows);
            }
        });
    }

    /**
     * Lazily load and cache the labels of a repository, indexed by github_id and name.
     */
    private function labelsForRepository(int $repositoryId, array &$cache): array
    {
        if (! isset($cache[$repositoryId])) {
            $byGithubId = [];
            $byName = [];

            foreach (DB::table('labels')->where('repository_id', $repositoryId)->get() as $label) {
                $byGithubId[$label->github_id] = $label->id;
                $byName[$label->name] = $label->id;
            }

            $cache[$repositoryId] = [
                'byGithubId' => $byGithubId,
                'byName' => $byName,
            ];
        }

        return $cache[$repositoryId];
    }
};
