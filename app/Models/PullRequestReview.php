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

    public function user()
    {
        return $this->belongsTo(GithubUser::class, 'user_id', 'id');
    }

    public function relatedComments()
    {
        // There is no 100% perfect way to know this, but we can make an educated guess
        // We cant now for sure becouse the github api does not return the review id in the comments + review comments are not always linked to a review
        // So the API will first create the review, then the comments, so we can assume that all comments created by the same user after the review creation time are related to this review
        // So we check all the comments from the same user on the same pull request created that have differ 1 minute after the review creation time
        return $this->hasMany(PullRequestComment::class, 'pull_request_id', 'pull_request_id')
            ->where('user_id', $this->user_id)
            ->where('created_at', '>=', $this->created_at->subMinute())
            ->where('created_at', '<=', $this->created_at->addMinutes(10));
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
