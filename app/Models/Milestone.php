<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\RepositoryService;
use App\GithubConfig;

class Milestone extends Model
{
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = false;

    protected $appends = [];

    protected $fillable = [
        'id',
        'repository_id',
        'number',
        'title',
        'body',
        'state',
        'labels',
        'opened_by_id',
        'type',
    ];

    protected $casts = [
        'labels' => 'array',
    ];
}
