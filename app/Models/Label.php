<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    protected $table = 'labels';
    protected $fillable = [
        'github_id',
        'repository_id',
        'name',
        'color',
        'description',
    ];

    public function repository()
    {
        return $this->belongsTo(Repository::class, 'repository_id', 'id');
    }
}
