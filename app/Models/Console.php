<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Console extends Model
{
    //
    public $fillable = [
        "command",
        "successful",
        "executed_at",
    ];
}
