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

    public function showNotice()
    {
        // If its master or maon, we don't show the notice
        $show = true;
        if (in_array($this->name, ['master', 'main'])) {
            $show = false;
        }

        // If it has a pull request, we don't show the notice
        if ($this->hasPullRequest) {
            $show = false;
        }

        // If its created more than 6 hours ago, we don't show the notice
        $sixHoursAgo = now()->subHours(6);
        if ($this->created_at < $sixHoursAgo) {
            $show = false;
        }

        return $show;
    }
}
