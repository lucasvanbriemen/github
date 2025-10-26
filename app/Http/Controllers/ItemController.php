<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Organization;
use App\Models\Repository;

class ItemController extends Controller
{
    public static function show($organizationName, $repositoryName, $issueNumber)
    {
        [$organization, $repository] = self::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $issueNumber)
            ->with(['assignees', 'openedBy', 'comments' => function($query) {
                $query->with('author');
            }])
            ->firstOrFail();

        return response()->json($item);
    }

  
    private static function getRepositoryWithOrganization($organizationName, $repositoryName)
    {
        $organization = Organization::where('name', $organizationName)->first();

        $query = Repository::with('organization')->where('name', $repositoryName)
            ->where('organization_id', $organization->id);
        $repository = $query->firstOrFail();

        return [$organization, $repository];
    }
}
