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

  public function openedBy()
  {
    return $this->belongsTo(RepositoryUser::class, "opened_by_id", "user_id");
  }

  public function assignees_data()
  {
    $assignees = $this->assignees;

    if (is_string($assignees)) {
        $assignees = json_decode($assignees, true) ?? [];
    }

    return RepositoryUser::whereIn('user_id', $assignees)
        ->groupBy('user_id')
        ->get();
  }

  public function comments()
  {
    return $this->hasMany(IssueComment::class, "issue_github_id", "github_id");
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
