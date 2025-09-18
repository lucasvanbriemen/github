<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Issue extends Model
{
  public function repository()
  {
    return $this->belongsTo(Repository::class, "repository_full_name", "full_name");
  }

  public $fillable = [
    "github_id",
    "repository_id",
    "opened_by_id",
    "number",
    "title",
    "body",
    "last_updated",
    "state",
    "labels",
    "assignees",
  ];

  protected $casts = [
    "labels" => "array",
    "assignees" => "array",
  ];
}
