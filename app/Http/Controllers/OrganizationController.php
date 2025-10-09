<?php

namespace App\Http\Controllers;

use App\Helpers\ApiHelper;
use App\Models\Organization;

class OrganizationController extends Controller
{
    public static function updateOrganizations()
    {
        $apiOrgs = ApiHelper::githubApi('/user/orgs');

        foreach ($apiOrgs as $apiOrg) {
            Organization::updateOrCreate(
                ['id' => $apiOrg->id],
                [
                    'name' => $apiOrg->login,
                    'description' => $apiOrg->description,
                    'avatar_url' => $apiOrg->avatar_url,
                ]
            );
        }

        return response()->json(['message' => 'Organizations updated successfully'], 200);
    }

    public function show($name)
    {
        $organization = Organization::where('name', $name)->firstOrFail();
        $repositories = $organization->repositories;

        return view('organization.show', compact('organization', 'repositories'));
    }
}
