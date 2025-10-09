<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViewedFile extends Model
{
    protected $table = 'viewed_files';

    public $fillable = [
        'file_path',
        'viewed',
        'branch_id',
    ];

    protected $casts = [
        'viewed' => 'boolean',
    ];
}
