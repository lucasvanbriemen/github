<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestedReviewer extends Model
{
    public function pullRequest()
    {
        return $this->belongsTo(PullRequest::class, 'pull_request_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(GithubUser::class, 'user_id', 'id');
    }

    protected $fillable = [
        'pull_request_id',
        'user_id',
        'state',
        'last_state_before_dismiss',
    ];
}
