<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Release extends Model
{
    public function repository()
    {
        return $this->belongsTo(Repository::class, 'repository_id', 'id');
    }

    public function author()
    {
        return $this->belongsTo(GithubUser::class, 'author_id', 'id');
    }

    public $fillable = [
        'github_id',
        'repository_id',
        'name',
        'description',
        'author_id',
        'status',
    ];
}
