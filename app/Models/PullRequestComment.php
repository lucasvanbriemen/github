<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BaseComment;

class PullRequestComment extends BaseComment
{
    protected $keyType = 'int';

    public $incrementing = false;

    public function baseComment()
    {
        return $this->belongsTo(BaseComment::class, 'base_comment_id', 'comment_id')
            ->where('type', 'code');
    }

    public function pullRequest()
    {
        return $this->belongsTo(PullRequest::class, 'pull_request_id', 'id');
    }

    public function author()
    {
        return $this->belongsTo(GithubUser::class, 'user_id', 'id');
    }

    public function childComments()
    {
        return $this->hasMany(PullRequestComment::class, 'in_reply_to_id', 'id');
    }

    protected $fillable = ['id', 'base_comment_id', 'diff_hunk', 'path', 'line_start', 'line_end', 'in_reply_to_id', 'resolved', 'side', 'original_line', 'pull_request_review_id'];
}
