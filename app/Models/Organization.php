<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Organization extends Model
{
    protected $primaryKey = 'id';

    protected $keyType = 'int';

    public $incrementing = false;

    public function repositories()
    {
        return $this->hasMany(Repository::class, 'organization_id', 'id')
            ->orderBy('last_updated', 'desc');
    }

    public $fillable = [
        'id',
        'name',
        'description',
        'avatar_url',
    ];
}
