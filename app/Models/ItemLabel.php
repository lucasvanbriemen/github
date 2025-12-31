<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemLabel extends Model
{
    public $fillable = [
        'item_id',
        'label_id'
    ];
}
