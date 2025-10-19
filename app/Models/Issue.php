<?php

namespace App\Models;

class Issue extends Item
{
    protected $table = 'items';

    protected static function booted()
    {
        static::addGlobalScope('type', function ($query) {
            $query->where('type', 'issue');
        });

        static::creating(function ($model) {
            $model->type = 'issue';
        });
    }

    protected $casts = [
        'labels' => 'array',
    ];
}
