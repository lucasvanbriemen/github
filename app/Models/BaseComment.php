<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseComment extends Model
{
    protected $table = 'base_comments';

    protected $fillable = ['comment_id', 'issue_id', 'user_id', 'body', 'created_at', 'updated_at', 'type', 'resolved'];

    protected $appends = ['details'];

    protected $hidden = ['reviewDetails', 'commentDetails'];

    public function issue()
    {
        return $this->belongsTo(Issue::class, 'issue_id', 'id');
    }

    public function author()
    {
        return $this->belongsTo(GithubUser::class, 'user_id', 'id');
    }

    public function reviewDetails()
    {
        return $this->hasOne(PullRequestReview::class, 'base_comment_id', 'id');
    }

    public function commentDetails()
    {
        return $this->hasOne(PullRequestComment::class, 'base_comment_id', 'id');
    }

    public function getDetailsAttribute()
    {
        if ($this->type === 'review') {
            return $this->relationLoaded('reviewDetails') ? $this->reviewDetails : $this->reviewDetails()->first();
        }

        if ($this->type === 'code') {
            return $this->relationLoaded('commentDetails') ? $this->commentDetails : $this->commentDetails()->first();
        }

        return null; // issue comments have no details
    }

    public function childComments()
    {
        // This relationship is primarily used for PR review comments
        // Since we can't easily define it directly on BaseComment, return an empty collection
        // Child comments are handled by PullRequestComment.childComments() instead
        return $this->hasMany(BaseComment::class, 'comment_id', 'id')->where('id', 0);
    }
}
