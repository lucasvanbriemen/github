<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Organization extends Model
{
    protected $primaryKey = 'github_id';

    protected $keyType = 'int';

    public $incrementing = false;

    public function repositories()
    {
        return $this->hasMany(Repository::class, 'organization_id', 'github_id')
            ->orderBy('last_updated', 'desc');
    }

    public $fillable = [
        'github_id',
        'name',
        'description',
        'avatar_url',
    ];
}
