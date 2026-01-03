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

    protected $fillable = [
        'type',
        'related_id',
        'completed',
        'triggered_by_id',
    ];
}
