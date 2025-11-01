<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PullRequestComment extends Model
{
    protected $keyType = 'int';

    public $incrementing = false;


    public function pullRequest()
    {
        return $this->belongsTo(PullRequest::class, 'pull_request_id', 'id');
    }

    public function author()
    {
        return $this->belongsTo(GithubUser::class, 'user_id', 'id');
    }

    public function replies()
    {
        return $this->hasMany(PullRequestComment::class, 'in_reply_to_id', 'id');
    }

    protected $fillable = ['id', 'pull_request_id', 'user_id', 'body', 'diff_hunk', 'path', 'line_start', 'line_end', 'in_reply_to_id', 'resolved', 'side', 'original_line', 'pull_request_review_id'];

}
