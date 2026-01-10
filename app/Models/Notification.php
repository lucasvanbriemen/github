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

    private static array $stateMap = [
        'approved' => 'approved',
        'changes_requested' => 'requested changes',
        'commented' => 'commented',
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
            return "{$this->comment->author->display_name} mentioned you in {$this->comment->item->title}";
        }

        if ($this->type === 'item_comment') {
            return "{$this->comment->author->display_name} commented on {$this->comment->item->title}";
        }

        if ($this->type === 'item_assigned') {
            return "{$this->item->title} was assigned to you";
        }

        if ($this->type === 'review_requested') {
            return "You were requested to review {$this->item->title}";
        }

        if ($this->type === 'pr_review') {
            return "{$this->review->baseComment->author->display_name} " . self::$stateMap[$this->review->state] . " on {$this->review->baseComment->item->title}";
        }

        return 'Notification';
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
