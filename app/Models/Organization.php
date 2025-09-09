<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Organization extends Model
{
  protected $keyType = "string";
  public $incrementing = false;

  public function repositories()
  {
    return $this->hasMany(Repository::class, "organization_id")
      ->orderBy("last_updated", "desc");
  }

  public static function booted()
  {
    static::creating(function ($organization) {
      $organization->id = (string) Str::uuid();
    });
  }

  public $fillable = [
    "organization_id",
    "name",
    "description",
    "avatar_url",
  ];
}
