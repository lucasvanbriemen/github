<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Console extends Model
{
    //
    protected $table = 'system_info';

    public $fillable = [
        "command",
        "successful",
        "executed_at",
    ];
}
