<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    protected $primaryKey = 'github_id';

    protected $keyType = 'int';

    public $incrementing = false;

    public $timestamps = true;

    public function repository()
    {
        return $this->belongsTo(Repository::class, 'repository_id', 'github_id');
    }

    public function openedBy()
    {
        return $this->belongsTo(GithubUser::class, 'opened_by_id', 'github_id');
    }

    public function assignees()
    {
        return $this->belongsToMany(GithubUser::class, 'issue_assignees', 'issue_id', 'github_user_id', 'github_id', 'github_id');
    }

    public function getAssigneesDataAttribute()
    {
        return $this->assignees()->get();
    }

    public function comments()
    {
        return $this->hasMany(IssueComment::class, 'issue_github_id', 'github_id');
    }

    protected $fillable = [
        'repository_id',
        'github_id',
        'number',
        'title',
        'body',
        'last_updated',
        'state',
        'labels',
        'assignees',
        'opened_by_id',
    ];

    protected $casts = [
        'labels' => 'array',
        'assignees' => 'array',
        'last_updated' => 'datetime',
    ];
}
