<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PullRequestReviewComment extends Model
{
    protected $table = 'pull_request_review_comments';

    protected $primaryKey = 'github_id';

    protected $keyType = 'int';

    public $incrementing = false;

    protected $fillable = [
        'github_id',
        'pull_request_github_id',
        'pull_request_review_github_id',
        'user_id',
        'body',
        'path',
        'diff_hunk',
        'commit_id',
        'in_reply_to_id',
    ];

    public function pullRequest()
    {
        return $this->belongsTo(PullRequest::class, 'pull_request_github_id', 'github_id');
    }

    public function review()
    {
        return $this->belongsTo(PullRequestReview::class, 'pull_request_review_github_id', 'github_id');
    }

    public function user()
    {
        return $this->belongsTo(GithubUser::class, 'user_id', 'github_id');
    }
}

