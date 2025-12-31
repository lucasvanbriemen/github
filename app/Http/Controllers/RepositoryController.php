<?php

namespace App\Http\Controllers;

use App\Helpers\ApiHelper;
use App\Services\RepositoryService;
use App\Models\Item;
use App\Models\Repository;
use App\Models\Label;
use Carbon\Carbon;

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

        $branches = $repository->branches()
            ->showNotice()
            ->whereHas('commits') // only branches that have at least one commit
            ->with(['commits' => function ($q) {
                $q->orderBy('created_at', 'desc')->limit(1);
            }])
            ->get()
            ->map(function ($branch) {
                $branch->last_commit = $branch->commits->first();
                unset($branch->commits);
                return $branch;
            });

        return response()->json($branches);
    }

    public static function updateLabels()
    {
        $repositories = Repository::all();

        foreach ($repositories as $repository) {
            $labels = ApiHelper::githubApi('/repos/'  .$repository->full_name . '/labels');

            foreach ($labels as $label) {
                // Update or create label
                Label::updateOrCreate(
                    [
                        'repository_id' => $repository->id,
                        'github_id' => $label->id,
                    ],
                    [
                        'name' => $label->name,
                        'color' => $label->color,
                        'description' => $label->description,
                    ]
                );
            }
        }
    }

    public function metadata($organizationName, $repositoryName)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $metadata = [
            'labels' => $repository->labels()->get(),
        ];

        return response()->json($metadata);
    }
}
