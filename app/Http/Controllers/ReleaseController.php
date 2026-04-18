<?php

namespace App\Http\Controllers;

use App\Services\RepositoryService;

class ReleaseController extends Controller
{
    public function index($organizationName, $repositoryName)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);
        return $repository->releases()->orderBy('created_at', 'desc')->get();
    }

    public function show(string $organizationName, string $repositoryName, int $releaseNumber)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);
        return $repository->releases()->where('number', $releaseNumber)->firstOrFail();
    }
}
