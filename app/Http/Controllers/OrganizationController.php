<?php

namespace App\Http\Controllers;
use App\Helpers\ApiHelper;
use App\Models\Organization;

class OrganizationController extends Controller
{
  public static function updateOrganizations()
  {
    $apiOrgs = ApiHelper::githubApi("/user/orgs");
    
    foreach ($apiOrgs as $apiOrg) {
      Organization::updateOrCreate(
        ["organization_id" => $apiOrg->id],
        [
          "name" => $apiOrg->login,
          "description" => $apiOrg->description,
          "avatar_url" => $apiOrg->avatar_url,
        ]
      );
    }

    return response()->json(["message" => "Organizations updated successfully"], 200);
  }

  public function show(Organization $organization)
  {
    $organization->load("repositories");
    return view("organization.show", compact("organization"));
  }
}
