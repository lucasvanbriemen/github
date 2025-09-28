<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GithubUser extends Model
{
    protected $primaryKey = 'github_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'github_id',
        'login',
        'name',
        'avatar_url',
        'type',
    ];

    public function repositories()
    {
        return $this->belongsToMany(Repository::class, 'repository_users', 'user_id', 'repository_id', 'github_id', 'github_id')
            ->withPivot('name', 'avatar_url')
            ->withTimestamps();
    }

    public function assignedIssues()
    {
        return $this->belongsToMany(Issue::class, 'issue_assignees', 'github_user_id', 'issue_id', 'github_id', 'github_id');
    }

    public function openedIssues()
    {
        return $this->hasMany(Issue::class, 'opened_by_id', 'github_id');
    }

    public function updateFromWebhook($userData)
    {
        return self::updateOrCreate(
            ['github_id' => $userData->id],
            [
                'login' => $userData->login ?? ($userData->name ?? ''),
                'name' => $userData->name ?? $userData->login ?? '',
                'avatar_url' => $userData->avatar_url ?? null,
                'type' => $userData->type ?? 'User',
            ]
        );
    }
}