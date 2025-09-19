<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepositoryUser extends Model
{
    //
    protected $fillable = [
        'repository_id',
        'user_id',
        'name',
        'avatar_url',
    ];

    public function repository()
    {
        return $this->belongsTo(Repository::class);
    }

    public function issues()
    {
        return $this->hasMany(Issue::class, 'opened_by_id', 'user_id');
    }
}
