<?php

namespace App\Models;

class PullRequest extends Item
{
    // PullRequest uses items table for base fields, pull_requests table for PR-specific fields
    protected $table = 'items';

    protected static function booted()
    {
        static::addGlobalScope('type', function ($query) {
            $query->where('type', 'pull_request');
        });

        static::creating(function ($model) {
            $model->type = 'pull_request';
        });
    }

    public function requestedReviewers()
    {
        return $this->hasMany(RequestedReviewer::class, 'pull_request_id', 'id');
    }

    // Relation to PR-specific data
    public function details()
    {
        return $this->hasOne(PullRequestDetails::class, 'id', 'id');
    }

    public function branch()
    {
        // Access head_branch via relationship
        $headBranch = $this->details->head_branch ?? null;

        if (!$headBranch) {
            return null;
        }

        return Branch::where('name', $headBranch)
            ->where('repository_id', $this->repository_id)
            ->first();
    }

    // Magic accessor to get PR-specific fields from details relationship
    public function __get($key)
    {
        // List of PR-specific fields stored in pull_requests table
        $prSpecificFields = ['head_branch', 'base_branch', 'head_sha', 'merge_base_sha', 'closed_at'];

        if (in_array($key, $prSpecificFields)) {
            // Load details if not already loaded
            if (!$this->relationLoaded('details')) {
                $this->load('details');
            }
            return $this->details->$key ?? null;
        }

        return parent::__get($key);
    }

    public function getReviewersDataAttribute()
    {
        return $this->requestedReviewers()->with('user')->get()->map(function ($reviewer) {
            $user = $reviewer->user;
            $user->state = $reviewer->state;
            return $user;
        });
    }

    // Override assignees to use issue_assignees (which now points to items)
    public function assignees()
    {
        return $this->belongsToMany(GithubUser::class, 'issue_assignees', 'issue_id', 'user_id', 'id', 'id');
    }

    public function pullRequestComments()
    {
        return $this->hasMany(PullRequestComment::class, 'pull_request_id', 'id');
    }

    public function pullRequestReviews()
    {
        return $this->hasMany(PullRequestReview::class, 'pull_request_id', 'id');
    }

    protected $casts = [
        'labels' => 'array',
        'closed_at' => 'datetime',
    ];

    public static function updateFromWebhook($prData)
    {
        // Update base fields in items table
        $pr = self::updateOrCreate(
            ['id' => $prData->id],
            [
                'repository_id' => $prData->base->repo->id,
                'number' => $prData->number,
                'title' => $prData->title,
                'body' => $prData->body,
                'state' => $prData->state === 'closed' && ($prData->merged ?? false) ? 'merged' : $prData->state,
                'opened_by_id' => $prData->user->id,
            ]
        );

        // Update PR-specific fields in pull_requests table if it exists
        if (\Schema::hasTable('pull_requests')) {
            \DB::table('pull_requests')->updateOrInsert(
                ['id' => $prData->id],
                [
                    'head_branch' => $prData->head->ref,
                    'base_branch' => $prData->base->ref,
                    'head_sha' => $prData->head->sha ?? null,
                    'merge_base_sha' => $prData->base->sha ?? null,
                    'closed_at' => $prData->closed_at ?? null,
                ]
            );
        }

        return $pr;
    }
}
