<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseComment extends Model
{
    protected $fillable = ['comment_id', 'issue_id', 'user_id', 'body', 'created_at', 'updated_at', 'type', 'resolved'];

    public function issue()
    {
        return $this->belongsTo(Issue::class, 'issue_id', 'id');
    }

    public function author()
    {
        return $this->belongsTo(GithubUser::class, 'user_id', 'id');
    }
}
