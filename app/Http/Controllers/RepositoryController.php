<?php

namespace App\Http\Controllers;

use App\Helpers\ApiHelper;
use App\Models\Organization;
use App\Models\Repository;
use Highlight\Highlighter;
use Illuminate\Http\Request;
use App\Services\RepositoryService;

class RepositoryController extends Controller
{
    public function getContributors($organizationName, $repositoryName)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $contributorsPivot = $repository->contributors()->get();
        $contributors = [];

        foreach ($contributorsPivot as $contributor) {
            $contributors[] = $contributor->githubUser;
        }

        return response()->json($contributors);
    }

    public function getBranchesForPRNotices($organizationName, $repositoryName)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $branches = $repository->branches()->get();
        $branchesForNotices = [];

        foreach ($branches as $branch) {
            if ($branch->showNotice()) {
                // Add the last commit
                $branch->load(['commits' => function ($q) {
                    $q->orderBy('created_at', 'desc')->limit(1);
                }]);

                if (!$branch->commits->isEmpty()) {
                    $branch->last_commit = $branch->commits->first();
                    $branch->last_commit->created_at_human = $branch->last_commit->created_at->diffForHumans();
                }
                
                $branchesForNotices[] = $branch;

            }
        }

        return response()->json($branchesForNotices);
    }
}
