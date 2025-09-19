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

  public function users()
  {
    return $this->hasMany(RepositoryUser::class);
  }

  public function issues()
  {
    return $this->hasMany(Issue::class, "repository_id", "id")
      ->orderBy("last_updated", "desc");
  }

  public $fillable = [
    "organization_id",
    "name",
    "github_id",
    "full_name",
    "private",
    "last_updated",
    "description",
    "pr_count",
    "issue_count",
  ];

  protected $casts = [
    "private" => "boolean",
    "last_updated" => "datetime",
  ];
}
