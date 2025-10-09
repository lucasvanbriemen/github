<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssueComment extends Model
{
    protected $primaryKey = 'id';

    protected $keyType = 'int';

    public $incrementing = false;

    protected $fillable = ['id', 'issue_id', 'user_id', 'body', 'created_at', 'updated_at', 'resolved'];

    public function issue()
    {
        return $this->belongsTo(Issue::class, 'issue_id', 'id');
    }

    public function author()
    {
        return $this->belongsTo(GithubUser::class, 'user_id', 'id');
    }
}
