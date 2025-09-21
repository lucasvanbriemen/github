<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PullRequestReview extends Model
{
    protected $table = 'pull_request_reviews';

    protected $primaryKey = 'github_id';

    protected $keyType = 'int';

    public $incrementing = false;

    protected $fillable = [
        'github_id',
        'pull_request_github_id',
        'user_id',
        'state',
        'body',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function pullRequest()
    {
        return $this->belongsTo(PullRequest::class, 'pull_request_github_id', 'github_id');
    }

    public function user()
    {
        return $this->belongsTo(GithubUser::class, 'user_id', 'github_id');
    }
}

