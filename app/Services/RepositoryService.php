<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Repository;

class RepositoryService
{
    public static function getRepositoryWithOrganization(string $organizationName, string $repositoryName): array
    {
        $organization = Organization::where('name', $organizationName)->firstOrFail();

        $repository = Repository::with('organization')
            ->where('name', $repositoryName)
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        return [$organization, $repository];
    }
}
