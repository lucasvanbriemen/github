<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Issue extends Model
{
  public $timestamps = true;

  public function repository()
  {
    return $this->belongsTo(Repository::class, "repository_id", "id");
  }

  protected $fillable = [
    "repository_id",
    "github_id",
    "number",
    "title",
    "body",
    "last_updated",
    "state",
    "labels",
    "assignees",
    "opened_by_id",
  ];

  protected $casts = [
    "labels" => "array",
    "assignees" => "array",
    "last_updated" => "datetime",
  ];
}
