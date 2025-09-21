<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PullRequestComment extends Model
{
    protected $table = 'pull_request_comments';

    protected $primaryKey = 'github_id';

    protected $keyType = 'int';

    public $incrementing = false;

    protected $fillable = [
        'github_id',
        'pull_request_github_id',
        'user_id',
        'body',
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

