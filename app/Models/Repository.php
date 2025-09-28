<?php

namespace App\Models;

use App\GithubConfig;
use Illuminate\Database\Eloquent\Model;

class Repository extends Model
{
    protected $primaryKey = 'github_id';

    protected $keyType = 'int';

    public $incrementing = false;

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id', 'github_id');
    }

    public function users()
    {
        return $this->hasMany(RepositoryUser::class, 'repository_id', 'github_id');
    }

    public function issues($state = 'open', $assignee = GithubConfig::USERID)
    {
        $query = $this->hasMany(Issue::class, 'repository_id', 'github_id')
            ->with('assignees', 'openedBy')
            ->orderBy('last_updated', 'desc');

        if ($state !== 'all') {
            $query->where('state', $state);
        }

        if ($assignee === 'none') {
            $query->whereDoesntHave('assignees');
        } elseif ($assignee !== 'any') {
            $query->whereHas('assignees', function ($q) use ($assignee) {
                $q->where('github_users.github_id', $assignee);
            });
        }

        return $query;
    }

    public function pullRequests($state = 'open', $assignee = GithubConfig::USERID)
    {
        $query = $this->hasMany(PullRequest::class, 'repository_id', 'github_id')
            ->with('assignees', 'openedBy')
            ->orderBy('updated_at', 'desc');

        if ($state !== 'all') {
            $query->where('state', $state);
        }

        if ($assignee === 'none') {
            $query->whereDoesntHave('assignees');
        } elseif ($assignee !== 'any') {
            $query->whereHas('assignees', function ($q) use ($assignee) {
                $q->where('github_users.github_id', $assignee);
            });
        }

        return $query;
    }

    public function updateFromWebhook($repoData)
    {
        return self::updateOrCreate(
            ['github_id' => $repoData->id],
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
        'github_id',
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
