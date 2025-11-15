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