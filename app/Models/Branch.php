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
        // If its master or maon, we don't show the notice
        $show = true;
        if (in_array($this->name, self::MASTER_BRANCHES)) {
            $show = false;
        }

        // If it has a pull request, we don't show the notice
        if ($this->hasPullRequest) {
            $show = false;
        }

        // If its created more than 6 hours ago, we don't show the notice
        $sixHoursAgo = now()->subHours(self::NOTICE_CREATED_TIME_HOURS);
        if ($this->created_at < $sixHoursAgo) {
            $show = false;
        }

        return $show;
    }
}
