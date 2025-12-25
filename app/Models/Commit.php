<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commit extends Model
{
    protected $primaryKey = 'sha';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $appends = [
        'created_at_human',
    ];

    public function getCreatedAtHumanAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function workflow()
    {
        return $this->hasOne(Workflow::class, 'id', 'workflow_id');
    }

    protected $fillable = [
        'sha',
        'repository_id',
        'branch_id',
        'user_id',
        'message',
        'workflow_id',
    ];
}
