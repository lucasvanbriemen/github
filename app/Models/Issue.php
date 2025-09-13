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
    "repository_full_name",
    "number",
    "title",
    "body",
    "last_updated",
    "state",
    "opened_by",
    "opened_by_image",
    "labels",
    "assignees"
  ];
}
