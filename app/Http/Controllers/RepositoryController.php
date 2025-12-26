<?php

namespace App\Http\Controllers;

use App\Helpers\ApiHelper;
use App\Services\RepositoryService;
use Illuminate\Support\Facades\Http;
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
        $query = <<<'GRAPHQL'
            query ($org: String!, $number: Int!, $after: String) {
                organization(login: $org) {
                    projectV2(number: $number) {
                        id
                        fields(first: 50) {
                            nodes {
                                ... on ProjectV2SingleSelectField {
                                    id
                                    name
                                    options {
                                        id
                                        name
                                    }
                                }
                            }
                        }
                        items(first: 100, after: $after) {
                            nodes {
                                id
                                content {
                                    ... on Issue {
                                        id
                                        title
                                        number
                                    }
                                    ... on PullRequest {
                                        id
                                        title
                                        number
                                    }
                                }
                                fieldValues(first: 20) {
                                    nodes {
                                        ... on ProjectV2ItemFieldSingleSelectValue {
                                            field {
                                                ... on ProjectV2SingleSelectField {
                                                    name
                                                }
                                            }
                                            optionId
                                            name
                                        }
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

        $items = $project->items->nodes;

        $statusField = collect($project->fields->nodes)
            ->first(fn ($f) => isset($f->name) && $f->name === 'Status');

        $columns = collect($statusField->options ?? [])
            ->mapWithKeys(fn ($option) => [
                $option->id => [
                    'id' => $option->id,
                    'name' => $option->name,
                    'items' => [],
                ],
            ]);

        foreach ($items as $item) {
            $statusValue = collect($item->fieldValues->nodes)
                ->first(fn ($fv) => isset($fv->field->name) && $fv->field->name === 'Status');

            $key = $statusValue->optionId;

            $column = $columns->get($key);

            $column['items'][] = [
                'id' => $item->id,
                'title' => $item->content->title,
                'number' => $item->content->number,
            ];

            $columns->put($key, $column);
        }

        return response()->json([ $columns,
        ]);
    }
}
