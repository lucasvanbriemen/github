<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    protected $table = 'labels';

    protected $fillable = [
        'github_id',
        'repository_id',
        'name',
        'color',
        'description',
    ];

    public function repository()
    {
        return $this->belongsTo(Repository::class, 'repository_id', 'id');
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'item_labels', 'label_id', 'item_id', 'id', 'id')
            ->withTimestamps();
    }

    /**
     * Upsert a set of GitHub label objects into the labels table for a repository
     * and return their local label ids (ready to pass to $item->labels()->sync()).
     *
     * Accepts GitHub label payloads as stdClass (webhooks) or arrays (REST client).
     */
    public static function syncFromGithub($repositoryId, $githubLabels): array
    {
        $labelIds = [];

        foreach ($githubLabels ?? [] as $label) {
            $label = (array) $label;
            $name = $label['name'] ?? null;

            if ($name === null) {
                continue;
            }

            // Match on (repository_id, name) to align with the unique constraint.
            $record = self::updateOrCreate(
                [
                    'repository_id' => $repositoryId,
                    'name' => $name,
                ],
                [
                    'github_id' => $label['id'] ?? 0,
                    'color' => $label['color'] ?? '000000',
                    'description' => $label['description'] ?? null,
                ]
            );

            $labelIds[] = $record->id;
        }

        return $labelIds;
    }
}
