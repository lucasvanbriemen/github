<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use GrahamCampbell\GitHub\Facades\GitHub;

class OrganizationController extends Controller
{
    public static function updateOrganizations()
    {
        foreach (GitHub::me()->organizations() as $organization) {
            Organization::updateOrCreate(
                ['id' => $organization->id],
                [
                    'name' => $organization->login,
                    'description' => $organization->description,
                    'avatar_url' => $organization->avatar_url,
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

    public static function getOrganizations()
    {
        $organizations = Organization::with('repositories')->get();
        return response()->json($organizations);
    }
}
