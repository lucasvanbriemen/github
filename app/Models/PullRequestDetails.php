<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PullRequestDetails extends Model
{
    protected $table = 'pull_requests';

    protected $primaryKey = 'id';

    protected $keyType = 'int';

    public $incrementing = false;

    public $timestamps = true;

    protected $fillable = [
        'id',
        'head_branch',
        'head_sha',
        'base_branch',
        'merge_base_sha',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public function pullRequest()
    {
        return $this->belongsTo(PullRequest::class, 'id', 'id');
    }
}
