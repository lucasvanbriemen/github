<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemInfo extends Model
{
    protected $table = 'system_info';

    public $fillable = [
        "api_url",
        "expires_at",
    ];

    public static function tokens_used(): int
    {
        return self::where('expires_at', '>', now()->subHour())->count();
    }

    public static function removeExpired(): void
    {
        self::where('expires_at', '<=', now()->subHour())->delete();
    }
}
