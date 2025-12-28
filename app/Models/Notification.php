<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BaseComment;

class Notification extends Model
{
    public function comment()
    {
        return $this->belongsTo(BaseComment::class, 'related_id', 'id');
    }

    public function actor()
    {
        return $this->belongsTo(GithubUser::class, 'actor_id', 'id');
    }

    public function repository()
    {
        return $this->belongsTo(Repository::class, 'repository_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'related_id', 'id');
    }

    public function workflow()
    {
        return $this->belongsTo(Workflow::class, 'related_id', 'id');
    }

    protected $fillable = [
        'type',
        'related_id',
        'completed',
        'actor_id',
        'repository_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
