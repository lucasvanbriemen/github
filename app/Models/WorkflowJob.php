<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowJob extends Model
{
    public $incrementing = true;

    protected $fillable = [
        'workflow_id',
        'name',
        'steps',
        'state',
        'conclusion'
    ];
}
