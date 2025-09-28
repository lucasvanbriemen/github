<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PullRequestComment extends Model
{
    protected $keyType = 'int';

    public $incrementing = false;


    public function pullRequest()
    {
        return $this->belongsTo(PullRequest::class, 'pull_request_github_id', 'github_id');
    }

    public function author()
    {
        return $this->belongsTo(GithubUser::class, 'user_id', 'github_id');
    }

    protected $fillable = ['id', 'pull_request_id', 'user_id', 'body', 'diff_hunk', 'path', 'line_start', 'line_end', 'in_reply_to_id'];

}
