<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViewedFile extends Model
{
    protected $table = 'viewed_files';

    public $fillable = [
        'file_path',
        'viewed',
        'pull_request_id',
    ];

    public function pullRequest()
    {
        return $this->belongsTo(PullRequest::class, 'pull_request_id', 'github_id');
    }

    public static function markAsViewed($pullRequestId, $filePath, $viewed = true)
    {
        return self::updateOrCreate(
            ['pull_request_id' => $pullRequestId, 'file_path' => $filePath],
            ['viewed' => $viewed]
        );
    }
}
