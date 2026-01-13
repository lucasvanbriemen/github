<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PullRequestDetails;
use App\Models\Commit;

class Branch extends Model
{
    const NOTICE_CREATED_TIME_HOURS = 6;
    const MASTER_BRANCHES = ['master', 'main'];

    public $fillable = [
        'updated_at',
        'created_at',
        'name',
        'repository_id'
    ];

    public function hasPullRequest()
    {
        return $this->hasOne(PullRequestDetails::class, 'head_branch', 'name');
    }

    public function commits()
    {
        return $this->hasMany(Commit::class, 'branch_id', 'id')
            ->orderBy('created_at', 'desc');
    }

    public function scopeShowNotice($query)
    {
        $sixHoursAgo = now()->subHours(self::NOTICE_CREATED_TIME_HOURS);

        return $query
            ->whereNotIn('name', self::MASTER_BRANCHES)
            ->where('created_at', '>=', $sixHoursAgo)
            ->whereDoesntHave('hasPullRequest');
    }
}
