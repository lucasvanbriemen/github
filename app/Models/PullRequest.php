<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PullRequest extends Model
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

    public function requestedReviewers()
    {
        return $this->hasMany(RequestedReviewer::class, 'pull_request_id', 'github_id');
    }

    public function getReviewersDataAttribute()
    {
        return $this->requestedReviewers()->with('user')->get()->map(function ($reviewer) {
            $user = $reviewer->user;
            $user->state = $reviewer->state;
            return $user;
        });
    }

    public function assignees()
    {
        return $this->belongsToMany(GithubUser::class, 'pull_request_assignees', 'pull_request_id', 'github_user_id', 'github_id', 'github_id');
    }

    public function comments()
    {
        return $this->hasMany(IssueComment::class, 'issue_github_id', 'github_id');
    }

    public function pullRequestComments()
    {
        return $this->hasMany(PullRequestComment::class, 'pull_request_id', 'github_id');
    }

    public function getAssigneesDataAttribute()
    {
        return $this->assignees()->get();
    }

    protected $fillable = [
        'github_id',
        'repository_id',
        'number',
        'title',
        'body',
        'state',
        'labels',
        'opened_by_id',
    ];

    protected $casts = [
        'labels' => 'array',
    ];
}
