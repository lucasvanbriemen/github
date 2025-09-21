<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssueComment extends Model
{
    protected $primaryKey = 'github_id';

    protected $keyType = 'int';

    public $incrementing = false;

    protected $fillable = ['github_id', 'issue_github_id', 'user_id', 'body'];

    public function issue()
    {
        return $this->belongsTo(Issue::class, 'issue_github_id', 'github_id');
    }

    public function author()
    {
        return $this->belongsTo(GithubUser::class, 'user_id', 'github_id');
    }
}
