<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Repository extends Model
{
  protected $keyType = "string";
  public $incrementing = false;

  public static function booted()
  {
    static::creating(function ($model) {
      if (empty($model->id)) {
        $model->id = (string) Str::uuid();
      }
    });
  }

  public function organization()
  {
    return $this->belongsTo(Organization::class, "organization_id", "organization_id");
  }

  public $fillable = [
    "organization_id",
    "name",
    "full_name",
    "private",
    "last_updated",
    "description",
    "pr_count",
    "issue_count",
  ];
}
