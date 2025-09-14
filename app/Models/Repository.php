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

  public function issues($state = null, $assignee = null)
  {
    $relation = $this->hasMany(Issue::class, "repository_full_name", "full_name")
      ->orderBy("last_updated", "desc");

    if ($state && in_array($state, ["open", "closed"])) {
      $relation->where("state", $state);
    }

    if ($assignee !== null && $assignee !== "") {
      if ($assignee === "unassigned") {
        $relation->where(function ($q) {
          $q->whereNull('assignees')
            ->orWhereRaw('JSON_LENGTH(assignees) = 0');
        });
      } else {
        $relation->whereJsonContains('assignees', ['login' => $assignee]);
      }
    }

    return $relation;
  }

  public function openIssues()
  {
    return $this->issues('open');
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
