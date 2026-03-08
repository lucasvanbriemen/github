<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowJob extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'workflow_id',
        'name',
        'steps',
        'state',
        'conclusion'
    ];
}
