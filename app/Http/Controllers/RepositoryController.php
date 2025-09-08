<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ApiHelper;
use App\Models\Organization;
use App\Models\Repository;

class RepositoryController extends Controller
{
  public static function updateRepositories()
  {
    $organizations = Organization::all();

    foreach ($organizations as $organization) {
      $apiRepos = ApiHelper::githubApi("/orgs/{$organization->name}/repos");

      foreach ($apiRepos as $apiRepo) {
        Repository::updateOrCreate(
          ["organization_id" => $organization->name, "name" => $apiRepo->name],
          [
            "full_name" => $apiRepo->full_name,
            "private" => $apiRepo->private,
          ]
        );
      }
    }

    // Get current user repositories
    $apiRepos = ApiHelper::githubApi("/user/repos");
    foreach ($apiRepos as $apiRepo) {
      Repository::updateOrCreate(
        ["organization_id" => null, "name" => $apiRepo->name],
        [
          "full_name" => $apiRepo->full_name,
          "private" => $apiRepo->private,
        ]
      );
    }
  }
}
