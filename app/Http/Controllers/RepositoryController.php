<?php

namespace App\Http\Controllers;

use App\Helpers\ApiHelper;
use App\Models\Organization;
use Highlight\Highlighter;
use App\Models\Repository;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        // It's a user repo, set organization to null
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

  public function show(Request $request, $organizationName, $repositoryName, $filePath = null)
  {
    // User repositories have "user" as organization name in the URL, while being null in the DB
    if ($organizationName === "user") {
      $organizationName = null;
    }

    $organization = Organization::where("name", $organizationName)->first();
    
    $query = Repository::where("name", $repositoryName);
    if ($organization) {
      $query->where("organization_id", $organization->id);
    }
    $repository = $query->firstOrFail();

    $isFile = $request->query("isFile", false);

    $filecontent = ApiHelper::githubApi("/repos/{$repository->full_name}/contents/" . ($filePath ?? ""));
    if ($isFile) {
      $filecontent = file_get_contents($filecontent->download_url);
      $hl = new Highlighter();
      $filecontent = $hl->highlightAuto($filecontent)->value;
    }

    return view("repository.show", compact("organization", "repository", "filecontent", "isFile"));
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
