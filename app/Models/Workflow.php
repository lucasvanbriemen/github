<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    public $incrementing = false;

    protected $with = ['jobs'];


    public function jobs()
    {
        return $this->hasMany(WorkflowJob::class, 'workflow_id', 'id');
    }

    protected $fillable = [
        'id',
        'name',
        'state',
        'conclusion'
    ];
}
