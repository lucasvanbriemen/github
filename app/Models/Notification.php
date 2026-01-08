<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BaseComment;
use App\Models\Item;
use App\Models\PullRequestReview;

class Notification extends Model
{
    protected $appends = [
        'created_at_human',
    ];

    public function comment()
    {
        return $this->belongsTo(BaseComment::class, 'related_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'related_id', 'id');
    }

    public function review()
    {
        return $this->belongsTo(PullRequestReview::class, 'related_id', 'id');
    }

    public function triggeredBy()
    {
        return $this->belongsTo(GithubUser::class, 'triggered_by_id', 'id');
    }

    public function getCreatedAtHumanAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function subject()
    {
        if ($this->type === 'comment_mention') {
            return 'You were mentioned in a comment';
        }

        if ($this->type === 'item_comment') {
            return 'New comment on an item you are watching';
        }

        if ($this->type === 'item_assigned') {
            return 'You were assigned to an item';
        }
    }

    public function loadRelatedData()
    {
        if ($this->type === 'comment_mention' || $this->type === 'item_comment') {
            $this->load('comment.item.repository');
        }

        if ($this->type === 'item_assigned' || $this->type === 'review_requested') {
            $this->load('item.repository');
        }

        if ($this->type === 'pr_review') {
            $this->load('review.baseComment.item.repository');
        }
    }

    protected $fillable = [
        'type',
        'related_id',
        'completed',
        'triggered_by_id',
    ];
}
