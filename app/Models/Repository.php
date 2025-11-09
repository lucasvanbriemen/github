<?php

namespace App\Models;

use App\GithubConfig;
use Illuminate\Database\Eloquent\Model;

class Repository extends Model
{
    protected $primaryKey = 'id';

    protected $keyType = 'int';

    public $incrementing = false;

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id', 'id');
    }

    public function contributors()
    {
        return $this->hasMany(RepositoryUser::class, 'repository_id', 'id');
    }

    public function issues($state = 'open', $assignee = 'any')
    {
        $query = $this->hasMany(Issue::class, 'repository_id', 'id');
        $query->with('assignees', 'openedBy');

        if ($state !== 'all') {
            $query->where('state', $state);
        }

        if ($assignee !== 'any') {
            $query->whereHas('assignees', function ($q) use ($assignee) {
                $q->where('github_users.id', $assignee);
            });
        }

        $query->orderBy('created_at', 'desc');

        return $query;
    }

    public function pullRequests($state = 'open', $assignees = [GithubConfig::USERID])
    {
        $query = $this->hasMany(PullRequest::class, 'repository_id', 'id')
            ->with('assignees', 'openedBy')
            ->orderBy('updated_at', 'desc');

        if ($state !== 'all') {
            $query->where('state', $state);
        }

        if (!in_array('any', $assignees)) {
            $query->whereHas('assignees', function ($q) use ($assignees) {
                $q->whereIn('github_users.id', $assignees);
            });
        }

        $query->orderBy('created_at', 'desc');

        return $query;
    }

    public function items($type, $state = null, $assignee = null)
    {
        if ($type === 'issue') {
            return $this->issues($state, $assignee);
        } elseif ($type === 'pr') {
            return $this->pullRequests($state, $assignee);
        }

        throw new \InvalidArgumentException("Invalid item type: $type");
    }

    public function branches()
    {
        return $this->hasMany(Branch::class, 'repository_id', 'id')
            ->orderBy('name', 'asc');
    }

    public static function updateFromWebhook($repoData)
    {
        return self::updateOrCreate(
            ['id' => $repoData->id],
            [
                'organization_id' => $repoData->owner->id,
                'name' => $repoData->name,
                'full_name' => $repoData->full_name,
                'private' => $repoData->private,
                'description' => $repoData->description ?? '',
                'last_updated' => now(),
            ]
        );
    }

    public $fillable = [
        'organization_id',
        'name',
        'id',
        'full_name',
        'private',
        'last_updated',
        'description',
        'pr_count',
        'issue_count',
    ];

    protected $casts = [
        'private' => 'boolean',
        'last_updated' => 'datetime',
    ];
}
