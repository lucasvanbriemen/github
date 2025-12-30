<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemLabel extends Model
{
    //
    protected $table = 'item_labels';

    public $fillable = [
        'item_id',
        'label_id'
    ];
}
