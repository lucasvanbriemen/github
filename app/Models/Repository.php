<?php

namespace App\Models;

use App\GithubConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
            ->orderBy('last_updated', 'desc');

        if ($state !== 'all') {
            $query->where('state', $state);
        }

        if ($assignee === 'none') {
            $query->where('assignees', '[]');
        } elseif ($assignee !== 'any') {
            $query->where(function ($q) use ($assignee) {
                $q->where('assignees', 'LIKE', '%'.$assignee.'%');
            });
        }

        return $query;
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
