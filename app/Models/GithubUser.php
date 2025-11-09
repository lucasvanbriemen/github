<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GithubUser extends Model
{
    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'login',
        'name',
        'avatar_url',
        'type',
        'display_name',
    ];

    public function repositories()
    {
        return $this->belongsToMany(Repository::class, 'repository_users', 'user_id', 'repository_id', 'id', 'id')
            ->withPivot('name', 'avatar_url')
            ->withTimestamps();
    }

    public function assignedIssues()
    {
        return $this->belongsToMany(Issue::class, 'issue_assignees', 'user_id', 'issue_id', 'id', 'id');
    }

    public function openedIssues()
    {
        return $this->hasMany(Issue::class, 'opened_by_id', 'id');
    }

    public static function updateFromWebhook($userData)
    {

        $existingUser = self::find($userData->id);
        $displayName = $existingUser ? $existingUser->display_name : $userData->name ;

        return self::updateOrCreate(
            ['id' => $userData->id],
            [
                'login' => $userData->login ?? ($userData->name ?? ''),
                'name' => $userData->name ?? $userData->login ?? '',
                'avatar_url' => $userData->avatar_url ?? null,
                'type' => $userData->type ?? 'User',
                'display_name' => $displayName
            ]
        );
    }
}