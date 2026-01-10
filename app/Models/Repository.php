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

    public function milestones()
    {
        return $this->hasMany(Milestone::class, 'repository_id', 'id')
            ->orderBy('due_on', 'asc');
    }

    public function labels()
    {
        return $this->hasMany(Label::class, 'repository_id', 'id')
            ->orderBy('name', 'asc');
    }

    public function issues($state = 'open', $assignee = 'any', $search = null)
    {
        $query = $this->hasMany(Issue::class, 'repository_id', 'id');
        $query->with('assignees', 'openedBy');

        if ($state !== 'all') {
            $query->where('state', $state);
        }

        if ($search) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        if ($assignee !== 'any') {
            $query->whereHas('assignees', function ($q) use ($assignee) {
                $q->where('github_users.id', $assignee);
            });
        }

        $query->orderBy('created_at', 'desc');

        return $query;
    }

    public function pullRequests($state = 'open', $assignee = 'any', $search = null)
    {
        $query = $this->hasMany(PullRequest::class, 'repository_id', 'id')
            ->with('assignees', 'openedBy');

        if ($state !== 'all') {
            $query->where('state', $state);
        }

        if ($search) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        if ($assignee !== 'any') {
            $query->whereHas('assignees', function ($q) use ($assignee) {
                $q->where('github_users.id', $assignee);
            });
        }

        $query->orderBy('created_at', 'desc');

        return $query;
    }

    public function items($type, $state = null, $assignee = null, $search = null)
    {
        if ($type === 'prs') {
            return $this->pullRequests($state, $assignee, $search);
        }

        return $this->issues($state, $assignee, $search);
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
                'master_branch' => $repoData->default_branch,
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
        'master_branch',
    ];

    protected $casts = [
        'private' => 'boolean',
        'last_updated' => 'datetime',
    ];
}
