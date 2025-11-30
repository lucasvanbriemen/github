<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BaseComment;

class PullRequestComment extends BaseComment
{
    protected $table = 'pull_request_comments';

    protected $keyType = 'int';

    public $incrementing = false;

    protected $with = ['childComments'];

    public function pullRequest()
    {
        return $this->belongsTo(PullRequest::class, 'pull_request_id', 'id');
    }

    public function author()
    {
        // Since user_id is now in base_comments, this relationship is set manually
        // in the controller by copying it from baseComment.author
        return $this->belongsTo(GithubUser::class, 'user_id', 'id');
    }

    public function childComments()
    {
        return $this->hasMany(PullRequestComment::class, 'in_reply_to_id', 'id');
    }

    public function childCommentsRecursive()
    {
        return $this->childComments()->with('childCommentsRecursive');
    }

    public function baseComment()
    {
        return $this->belongsTo(BaseComment::class, 'base_comment_id', 'id')
            ->where('type', 'code');
    }

    protected $fillable = ['id', 'base_comment_id', 'diff_hunk', 'path', 'line_start', 'line_end', 'in_reply_to_id', 'resolved', 'side', 'original_line', 'pull_request_review_id'];

    protected $appends = ['body', 'user_id'];

    public function getBodyAttribute()
    {
        // Get body from the baseComment relationship if loaded
        if ($this->relationLoaded('baseComment')) {
            return $this->baseComment?->body;
        }
        return null;
    }

    public function getUserIdAttribute()
    {
        // Get user_id from the baseComment relationship if loaded
        if ($this->relationLoaded('baseComment')) {
            return $this->baseComment?->user_id;
        }
        return null;
    }
}
