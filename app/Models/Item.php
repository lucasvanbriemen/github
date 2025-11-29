<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'items';
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = true;

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
    ];

    protected $casts = [
        'labels' => 'array',
    ];

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
        return $this->hasMany(BaseComment::class, 'issue_id', 'id')
            ->where('type', 'issue');
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

    public function pullRequestComments()
    {
        // Return PullRequestComment instances which have the childComments relationship
        // Join with base_comments to filter by issue_id
        return $this->hasManyThrough(
            PullRequestComment::class,
            BaseComment::class,
            'issue_id',          // Foreign key on base_comments table
            'base_comment_id',   // Foreign key on pull_request_comments table
            'id',                // Local key on items table
            'comment_id'         // Local key on base_comments table
        )->where('base_comments.type', 'code')
         ->whereNull('pull_request_comments.in_reply_to_id'); // Only top-level comments
    }
}
