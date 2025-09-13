<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemInfo extends Model
{
    protected $table = 'system_info';

    public $fillable = [
        'api_count',
    ];
}
