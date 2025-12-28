<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BaseComment;
use App\Models\Item;
use App\Models\PullRequestReview;

class Notification extends Model
{
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

    protected $fillable = [
        'type',
        'related_id',
        'completed',
    ];
}
