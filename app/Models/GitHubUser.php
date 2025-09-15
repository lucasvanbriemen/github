<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GitHubUser extends Model
{
    protected $table = 'github_users';

    protected $fillable = [
        'github_id',
        'login',
        'name',
        'avatar_url',
        'type'
    ];

    public static function createOrUpdate($userData)
    {
        return self::updateOrCreate(
            ['github_id' => $userData['id']],
            [
                'login' => $userData['login'],
                'name' => $userData['name'] ?? null,
                'avatar_url' => $userData['avatar_url'],
                'type' => $userData['type'] ?? 'User'
            ]
        );
    }
}
