<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PullRequest extends Model
{
    protected $primaryKey = 'github_id';

    protected $keyType = 'int';

    public $incrementing = false;

    public $timestamps = true;

    public function repository()
    {
        return $this->belongsTo(Repository::class, 'repository_id', 'github_id');
    }

    public function openedBy()
    {
        return $this->belongsTo(GithubUser::class, 'opened_by_id', 'github_id');
    }

    protected $fillable = [
        'github_id',
        'repository_id',
        'number',
        'title',
        'body',
        'state',
        'opened_by_id',
    ];
}
