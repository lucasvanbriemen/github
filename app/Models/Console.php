<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Console extends Model
{
    protected $table = 'console';

    public $fillable = [
        "command",
        "successful",
        "executed_at",
    ];
}
