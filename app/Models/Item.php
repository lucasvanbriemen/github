<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\RepositoryService;
use App\GithubConfig;

class Item extends Model
{
    protected $table = 'items';
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = true;

    protected $appends = [
        'created_at_human',
    ];

    protected $fillable = [
        'id',
        'repository_id',
        'number',
        'title',
        'body',
        'state',
        'labels',
        'opened_by_id',
        'type',
        'milestone_id',
    ];

    protected $casts = [
        'labels' => 'array',
    ];

    public function getBodyAttribute($value)
    {
        return RepositoryService::processMarkdownImages($value);
    }

    public function getCreatedAtHumanAttribute()
    {
        return $this->created_at?->diffForHumans();
    }

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

    public function isCurrentlyAssignedToUser($githubUserId = GithubConfig::USERID)
    {
        return $this->assignees()->where('github_users.id', $githubUserId)->exists();
    }

    public function getAssigneesDataAttribute()
    {
        return $this->assignees()->get();
    }

    public function comments()
    {
        return $this->hasMany(BaseComment::class, 'issue_id', 'id')
            ->whereDoesntHave('commentDetails', function ($q) {
                $q->whereNotNull('pull_request_review_id');
            })
            ->where('type', '!=', 'code');
    }

    // Scope to get only issues
    public function scopeIssues($query)
    {
        return $query->where('type', 'issue');
    }

    // Scope to get only pull requests
    public function scopePullRequests($query)
    {
        return $query->where('type', 'pull_request');
    }

    public function isPullRequest()
    {
        return $this->type === 'pull_request';
    }

    // PR-specific relationships (only populated for pull requests)
    public function details()
    {
        return $this->hasOne(PullRequestDetails::class, 'id', 'id');
    }

    public function requestedReviewers()
    {
        return $this->hasMany(RequestedReviewer::class, 'pull_request_id', 'id');
    }

    public function pullRequestReviews()
    {
        return $this->hasMany(PullRequestReview::class, 'pull_request_id', 'id');
    }

    public function getLatestCommitSha()
    {
        return $this->details()->first()->head_sha;
    }

    public function milestone()
    {
        return $this->belongsTo(Milestone::class, 'milestone_id', 'id');
    }
}
