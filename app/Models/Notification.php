<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public function comment()
    {
        return $this->belongsTo(BaseComment::class, 'related_id', 'id');
    }

    protected $fillable = [
        'type',
        'related_id',
        'completed',
    ];
}
