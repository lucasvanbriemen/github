<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BaseComment;

class PullRequestComment extends BaseComment
{
    protected $table = 'pull_request_comments';

    protected $keyType = 'int';

    public $incrementing = false;

    // protected $with = ['childComments', 'baseComment'];
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
        return $this->hasMany(PullRequestComment::class, 'in_reply_to_id', 'id')
            ->with(['author', 'baseComment']);
    }

    public function parentComment()
    {
        return $this->belongsTo(PullRequestComment::class, 'in_reply_to_id', 'id');
    }

    public function baseComment()
    {
        return $this->belongsTo(BaseComment::class, 'base_comment_id', 'id')
            ->where('type', 'code');
    }

    protected $fillable = ['id', 'base_comment_id', 'diff_hunk', 'path', 'line_start', 'line_end', 'in_reply_to_id', 'resolved', 'side', 'original_line', 'pull_request_review_id'];

    public function unresolveParentIfResolved(): void
    {
        if ($this->in_reply_to_id) {
            $parentComment = self::find($this->in_reply_to_id);
            if ($parentComment) {
                $parentComment->baseComment->resolved = false;
                $parentComment->baseComment->save();
                $parentComment->unresolveParentIfResolved();
            }
        }
    }
}
