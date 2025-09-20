<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\GithubConfig;

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

  public function issues($state = "open", $assignee = GithubConfig::USERID)
  {
    $query = $this->hasMany(Issue::class, "repository_id", "id")
      ->orderBy("last_updated", "desc");

    if ($state !== "all") {
      $query->where("state", $state);
    }

    if ($assignee === "none") {
      $query->where("assignees", "[]");
    } elseif ($assignee !== "any") {
      // Handle both simple ID arrays and user object arrays
      $query->where(function($q) use ($assignee) {
        // Try exact JSON contains first (for simple array format)
        $q->whereJsonContains('assignees', (int)$assignee)
          ->orWhereJsonContains('assignees', (string)$assignee)
          // Fallback to LIKE for user object format
          ->orWhere('assignees', 'LIKE', '%' . $assignee . '%');
      });
    }

    return $query;
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
