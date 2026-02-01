<?php

namespace App\Http\Controllers;

use App\Helpers\ApiHelper;
use App\Services\RepositoryService;
use App\Services\BranchTreeService;
use App\Models\Repository;
use App\Models\Label;
use App\GithubConfig;

class RepositoryController extends Controller
{
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

        $branchNames = $repository->branches()->pluck('name');
        $master_branch = $repository->master_branch;
        $default_assignee = GithubConfig::USERNAME;
        $labels = $repository->labels()->get();
        $milestones = $repository->milestones()->get();

        $assignees = $repository->contributors()->with('githubUser')->get()->map(function ($contributor) {
            return $contributor->githubUser;
        })->values();

        $templatesJson = file_get_contents(resource_path('repository_templates/templates.json'));
        $templates = json_decode($templatesJson, true);

        return response()->json([
            'branches' => $branchNames,
            'assignees' => $assignees,
            'default_assignee' => $default_assignee,
            'master_branch' => $master_branch,
            'templates' => $templates,
            'labels' => $labels,
            'milestones' => $milestones,
        ]);
    }

    public function getBranchTree($organizationName, $repositoryName)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $service = new BranchTreeService();
        $branches = $service->buildTree($repository->id);

        return response()->json(['branches' => $branches]);
    }
}
