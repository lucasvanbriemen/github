<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PullRequestReview extends Model
{
    protected $table = 'pull_request_reviews';

    protected $keyType = 'int';

    public $incrementing = false;

    public $timestamps = true;

    public function pullRequest()
    {
        return $this->belongsTo(PullRequest::class, 'pull_request_id', 'id');
    }

    public function author()
    {
        return $this->belongsTo(GithubUser::class, 'user_id', 'id');
    }

    public function childComments()
    {
        $query = $this->hasMany(PullRequestComment::class, 'pull_request_review_id', 'id');
        $query->with(['author', 'childComments.author'])->whereNull('in_reply_to_id')->orderBy('created_at', 'asc');

        return $query;
    }

    protected $fillable = [
        'id',
        'pull_request_id',
        'user_id',
        'body',
        'state',
        'resolved',
    ];
}
