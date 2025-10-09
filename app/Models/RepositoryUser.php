<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepositoryUser extends Model
{
    protected $primaryKey = ['repository_id', 'user_id'];

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'repository_id',
        'user_id',
    ];

    public function repository()
    {
        return $this->belongsTo(Repository::class, 'repository_id', 'id');
    }

    public function githubUser()
    {
        return $this->belongsTo(GithubUser::class, 'user_id', 'id');
    }

    public function issues()
    {
        return $this->hasMany(Issue::class, 'opened_by_id', 'user_id');
    }
}
