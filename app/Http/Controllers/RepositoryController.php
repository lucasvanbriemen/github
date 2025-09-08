<?php

namespace App\Http\Controllers;

use App\Helpers\ApiHelper;
use App\Models\Organization;
use App\Models\Repository;
use Carbon\Carbon;

class RepositoryController extends Controller
{
  public static function updateRepositories()
  {
    self::updateOrganizationRepositories();
    self::updateUserRepositories();
  }

  public static function updateUserRepositories()
  {
    $apiRepos = ApiHelper::githubApi("/user/repos");
    foreach ($apiRepos as $apiRepo) {

      // If the repo belongs to an organization you own, find its ID
      if ($apiRepo->owner->type === "Organization") {
        $organization = Organization::where("name", $apiRepo->owner->login)->first();
      } else {
        $organization = new Organization();
        $organization->id = null;
      }

      self::updateApiRepository($organization, $apiRepo);
    }
  }

  public static function updateOrganizationRepositories()
  {
    $organizations = Organization::all();

    foreach ($organizations as $organization) {
      $apiRepos = ApiHelper::githubApi("/orgs/{$organization->name}/repos");
      foreach ($apiRepos as $apiRepo) {
        self::updateApiRepository($organization, $apiRepo);
      }
    }
  }

  private static function updateApiRepository($organization, $apiRepo)
  {
    Repository::updateOrCreate(
      ["organization_id" => $organization->id, "name" => $apiRepo->name],
      [
        "full_name" => $apiRepo->full_name,
        "private" => $apiRepo->private,
        "last_updated" => Carbon::parse($apiRepo->updated_at)->format("Y-m-d H:i:s"),
      ]
    );
  }
}
