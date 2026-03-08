<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\RepositoryService;

class BaseComment extends Model
{
    protected $table = 'base_comments';

    protected $fillable = ['comment_id', 'issue_id', 'user_id', 'body', 'created_at', 'updated_at', 'type', 'resolved'];

    protected $with  = ['author', 'reviewDetails', 'commentDetails'];

    protected $appends = [
        'created_at_human',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'issue_id', 'id');
    }

    public function getBodyAttribute($value)
    {
        return RepositoryService::processMarkdownImages($value);
    }

    public function getCreatedAtHumanAttribute()
    {
        return $this->created_at?->diffForHumans();
    }

    public function author()
    {
        return $this->belongsTo(GithubUser::class, 'user_id', 'id');
    }

    public function reviewDetails()
    {
        return $this->hasOne(PullRequestReview::class);
    }

    public function commentDetails()
    {
        return $this->hasOne(PullRequestComment::class);
    }
}
