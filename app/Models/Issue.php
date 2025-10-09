<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    protected $primaryKey = 'id';

    protected $keyType = 'int';

    public $incrementing = false;

    public $timestamps = true;

    public function repository()
    {
        return $this->belongsTo(Repository::class, 'repository_id', 'id');
    }

    public function openedBy()
    {
        return $this->belongsTo(GithubUser::class, 'opened_by_id', 'id');
    }

    public function assignees()
    {
        return $this->belongsToMany(GithubUser::class, 'issue_assignees', 'issue_id', 'user_id', 'id', 'id');
    }

    public function getAssigneesDataAttribute()
    {
        return $this->assignees()->get();
    }

    public function comments()
    {
        return $this->hasMany(IssueComment::class, 'issue_id', 'id');
    }

    protected $fillable = [
        'repository_id',
        'id',
        'number',
        'title',
        'body',
        'last_updated',
        'state',
        'labels',
        'opened_by_id',
    ];

    protected $casts = [
        'labels' => 'array',
        'last_updated' => 'datetime',
    ];
}
