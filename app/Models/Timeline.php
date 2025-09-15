<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Timeline extends Model
{
    protected $table = 'timeline';

    protected $fillable = [
        'github_id',
        'issue_id',
        'event',
        'actor_github_id',
        'data',
        'created_at_github'
    ];

    protected $casts = [
        'data' => 'array',
        'created_at_github' => 'datetime'
    ];

    public function issue()
    {
        return $this->belongsTo(Issue::class);
    }

    public function actor()
    {
        return $this->belongsTo(GitHubUser::class, 'actor_github_id', 'github_id');
    }
}
