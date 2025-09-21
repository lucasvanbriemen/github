<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PullRequest extends Model
{
    protected $table = 'pull_requests';

    protected $primaryKey = 'github_id';

    protected $keyType = 'int';

    public $incrementing = false;

    protected $fillable = [
        'github_id',
        'repository_id',
        'number',
        'title',
        'body',
        'state',
        'draft',
        'author_id',
        'source_branch',
        'target_branch',
        'labels',
    ];

    protected $casts = [
        'labels' => 'array',
        'draft' => 'boolean',
    ];

    public function repository()
    {
        return $this->belongsTo(Repository::class, 'repository_id', 'github_id');
    }

    public function author()
    {
        return $this->belongsTo(GithubUser::class, 'author_id', 'github_id');
    }

    public function reviewers()
    {
        return $this->belongsToMany(
            GithubUser::class,
            'pull_request_reviewers',
            'pull_request_github_id',
            'github_user_id',
            'github_id',
            'github_id'
        );
    }

    public function assignees()
    {
        return $this->belongsToMany(
            GithubUser::class,
            'pull_request_assignees',
            'pull_request_github_id',
            'github_user_id',
            'github_id',
            'github_id'
        );
    }

    public function reviews()
    {
        return $this->hasMany(PullRequestReview::class, 'pull_request_github_id', 'github_id')
            ->orderBy('submitted_at', 'asc');
    }

    public function reviewComments()
    {
        return $this->hasMany(PullRequestReviewComment::class, 'pull_request_github_id', 'github_id')
            ->latest();
    }
}

