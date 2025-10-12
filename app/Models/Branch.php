<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PullRequest;

class Branch extends Model
{
    const NOTICE_CREATED_TIME_HOURS = 6;
    const MASTER_BRANCHES = ['master', 'main'];

    public $fillable = [
        'updated_at',
        'name',
        'repository_id'
    ];

    public function hasPullRequest()
    {
        return $this->hasOne(PullRequest::class, 'head_branch', 'name');
    }

    public function showNotice()
    {
        $show = true;
        if (in_array($this->name, self::MASTER_BRANCHES)) {
            $show = false;
        }

        if ($this->hasPullRequest) {
            $show = false;
        }

        $sixHoursAgo = now()->subHours(self::NOTICE_CREATED_TIME_HOURS);
        if ($this->created_at < $sixHoursAgo) {
            $show = false;
        }

        return $show;
    }
}
