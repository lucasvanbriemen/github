<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PullRequestReview extends Model
{
    protected $table = 'pull_request_reviews';

    protected $keyType = 'int';

    public $incrementing = false;

    public $timestamps = true;

    public function pullRequest()
    {
        return $this->belongsTo(PullRequest::class, 'pull_request_id', 'github_id');
    }

    public function user()
    {
        return $this->belongsTo(GithubUser::class, 'user_id', 'github_id');
    }

    protected $fillable = [
        'id',
        'pull_request_id',
        'user_id',
        'body',
        'state'
    ];
}
