<?php

namespace App\Http\Controllers;

use App\Helpers\ApiHelper;
use App\Services\RepositoryService;
use App\Models\Item;
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

    public function getProjects($organizationName, $repositoryName)
    {
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

        $response = ApiHelper::githubGraphql($mutation, ['owner' => $organizationName, 'name' => $repositoryName,]);
        $data = $response->data->repository->projectsV2->nodes ?? [];

        $projects = [];
        foreach ($data as $project) {
            $projects[] = [
                'id' => $project->id,
                'title' => $project->title,
                'number' => $project->number,
                'updated_at' => Carbon::parse($project->updatedAt)->diffForHumans(),
            ];
        }

        return response()->json($projects);
    }

    public function showProject(string $organizationName, string $repositoryName, int $projectNumber)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $query = <<<'GRAPHQL'
            query ($org: String!, $number: Int!, $after: String) {
                organization(login: $org) {
                    projectV2(number: $number) {
                        field(name: "Status") {
                            ... on ProjectV2SingleSelectField {
                                name
                                options {
                                    name
                                }
                            }
                        }
                        items(first: 100, after: $after) {
                            nodes {
                                content {
                                    ... on Issue {
                                        number
                                    }
                                    ... on PullRequest {
                                        number
                                    }
                                }
                                fieldValueByName(name: "Status") {
                                    ... on ProjectV2ItemFieldSingleSelectValue {
                                        name
                                    }
                                }
                            }
                        }
                    }
                }
            }
        GRAPHQL;

        $project = ApiHelper::githubGraphql($query, [
            'org' => $organizationName,
            'number' => (int) $projectNumber,
        ])->data->organization->projectV2;

        $columns = collect(
            $project->field->options
        )->mapWithKeys(fn ($option) => [
            $option->name => [
                'name' => $option->name,
                'items' => [],
            ],
        ]);

        $allIds = [];
        foreach ($project->items->nodes as $item) {
            $allIds[] = $item->content->number;
        }

        // Get all the items from db
        $DBitems = Item::whereIn('number', $allIds)
            ->where('repository_id', $repository->id)
            ->with([
                'assignees'
            ])
            ->get()
            ->keyBy('number');

        foreach ($project->items->nodes as $item) {
            $columnName = $item->fieldValueByName->name ?? 'Unassigned';
            $column = $columns->get($columnName);

            // If there is no matching item in the database, skip it
            if (!isset($DBitems[$item->content->number])) {
                continue;
            }

            $column['items'][] = $DBitems->get($item->content->number);

            $columns->put($columnName, $column);
        }

        return response()->json(array_values($columns->toArray()));
    }
}
