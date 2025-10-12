<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PullRequest;

class Branch extends Model
{
    //
    public $fillable = [
        'updated_at',
        'name',
        'repository_id'
    ];

    public function hasPullRequest()
    {
        return $this->hasOne(PullRequest::class, 'head_branch', 'name');
    }
}
