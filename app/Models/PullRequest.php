<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PullRequest extends Model
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

    public function requestedReviewers()
    {
        return $this->hasMany(RequestedReviewer::class, 'pull_request_id', 'id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'head_branch', 'name')
            ->where('repository_id', $this->repository_id);
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
        return $this->belongsToMany(GithubUser::class, 'pull_request_assignees', 'pull_request_id', 'user_id', 'id', 'id');
    }

    public function comments()
    {
        return $this->hasMany(IssueComment::class, 'issue_id', 'id');
    }

    public function pullRequestComments()
    {
        return $this->hasMany(PullRequestComment::class, 'pull_request_id', 'id');
    }

    public function pullRequestReviews()
    {
        return $this->hasMany(PullRequestReview::class, 'pull_request_id', 'id');
    }

    public function getAssigneesDataAttribute()
    {
        return $this->assignees()->get();
    }
    protected $fillable = [
        'id',
        'repository_id',
        'number',
        'title',
        'body',
        'state',
        'closed_at',
        'labels',
        'opened_by_id',
        'head_branch',
        'head_sha',
        'base_branch',
        'merge_base_sha',
    ];

    protected $casts = [
        'labels' => 'array',
        'closed_at' => 'datetime',
    ];

    public static function updateFromWebhook($prData)
    {
        return self::updateOrCreate(
            ['id' => $prData->id],
            [
                'repository_id' => $prData->base->repo->id,
                'number' => $prData->number,
                'title' => $prData->title,
                'body' => $prData->body,
                'state' => $prData->state,
                'opened_by_id' => $prData->user->id,
                'head_branch' => $prData->head->ref,
                'base_branch' => $prData->base->ref,
            ]
        );
    }
}
