<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commit extends Model
{
    protected $primaryKey = 'sha';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'sha',
        'repository_github_id',
        'branch_id',
        'github_user_id',
        'message',
    ];
}
