<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BaseComment;

class PullRequestReview extends BaseComment
{
    protected $table = 'pull_request_reviews';

    protected $keyType = 'int';

    public $incrementing = false;

    public $timestamps = true;

    protected $with = ['childComments'];

    public function pullRequest()
    {
        return $this->belongsTo(PullRequest::class, 'pull_request_id', 'id');
    }

    public function baseComment()
    {
        return $this->belongsTo(BaseComment::class, 'base_comment_id', 'id')
            ->where('type', 'review');
    }

    public function childComments()
    {
        // return $this->hasMany(PullRequestComment::class, 'pull_request_review_id', 'id')
        //     ->where('in_reply_to_id', null)
        //     ->orderBy('created_at', 'asc')
        //     ->with(['author', 'childComments']);

        $query = $this->hasMany(PullRequestComment::class, 'pull_request_review_id', 'id');
        $query->with(['author', 'childComments.author'])->whereNull('in_reply_to_id')->orderBy('created_at', 'asc');

        return $query;
    }

    protected $fillable = [
        'id',
        'base_comment_id',
        'state',
        'resolved',
    ];
}
