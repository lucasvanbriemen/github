<?php

namespace App\Http\Controllers;

use App\Services\RepositoryService;
use Illuminate\Support\Facades\Http;

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

    public function getProjects($organizationName, $repositoryName)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        // query projects from GitHub API and return them using graphql
        $mutation = <<<'GRAPHQL'
        query ($owner: String!, $name: String!) {
          repository(owner: $owner, name: $name) {
            projectsV2(first: 100) {
              nodes {
                id
                title
                number
                updatedAt
              }
            }
          }
        }
        GRAPHQL;

        $response = Http::withToken(config('services.github.access_token'))->post('https://api.github.com/graphql', [
            'query' => $mutation,
            'variables' => [
                'owner' => $organization->name,
                'name' => $repository->name,
            ],
        ]);

        $data = $response->json();
        $data = $data['data']['repository']['projectsV2']['nodes'] ?? [];

        $projects = [];
        foreach ($data as $project) {
            $projects[] = [
                'id' => $project['id'],
                'title' => $project['title'],
                'number' => $project['number'],
                'updated_at' => $project['updatedAt']->diffForHumans(),
            ];
        }

        return response()->json($projects);
    }
}
