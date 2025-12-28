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
                        id
                        field(name: "Status") {
                            ... on ProjectV2SingleSelectField {
                                id
                                name
                                options {
                                    id
                                    name
                                }
                            }
                        }
                        items(first: 100, after: $after) {
                            pageInfo {
                                hasNextPage
                                endCursor
                            }
                            nodes {
                                id
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
                                        optionId
                                    }
                                }
                            }
                        }
                    }
                }
            }
        GRAPHQL;

        $projectData = ApiHelper::githubGraphql($query, [
            'org' => $organizationName,
            'number' => (int) $projectNumber,
        ])->data->organization->projectV2;

        $projectId = $projectData->id;
        $fieldId = $projectData->field->id ?? null;

        $columns = collect(
            $projectData->field->options
        )->mapWithKeys(fn ($option) => [
            $option->name => [
                'name' => $option->name,
                'id' => $option->id,
                'items' => [],
            ],
        ]);

        $allItems = [];
        $after = null;

        do {
            $project = ApiHelper::githubGraphql($query, [
                'org' => $organizationName,
                'number' => (int) $projectNumber,
                'after' => $after,
            ])->data->organization->projectV2;

            foreach ($project->items->nodes as $item) {
                $allItems[] = $item;
            }

            $hasNextPage = $project->items->pageInfo->hasNextPage ?? false;
            $after = $project->items->pageInfo->endCursor ?? null;
        } while ($hasNextPage && $after);

        $allIds = [];
        foreach ($allItems as $item) {
            $allIds[] = $item->content->number;
        }

        $DBitems = Item::whereIn('number', $allIds)
            ->where('repository_id', $repository->id)
            ->with([
                'assignees'
            ])
            ->get()
            ->keyBy('number');

        // Group items by column
        foreach ($allItems as $item) {
            $columnName = $item->fieldValueByName->name ?? 'Unassigned';
            $column = $columns->get($columnName);

            if (!isset($DBitems[$item->content->number])) {
                continue;
            }

            // Include the GitHub project item ID so we can update it
            $dbItem = $DBitems->get($item->content->number);
            $dbItem->projectItemId = $item->id;
            $column['items'][] = $dbItem;

            $columns->put($columnName, $column);
        }

        return response()->json([
            'projectId' => $projectId,
            'fieldId' => $fieldId,
            'columns' => array_values($columns->toArray())
        ]);
    }

    public function addProjectItem(string $organizationName, string $repositoryName, int $projectNumber)
    {
        $mutation = <<<'GRAPHQL'
        mutation ($input: ProjectsV2AddDraftItemInput!) {
            projectsV2AddDraftItem(input: $input) {
                item {
                    id
                }
            }
        }
        GRAPHQL;

        $projectId = request()->input('projectId');
        $title = request()->input('title');

        $response = ApiHelper::githubGraphql($mutation, [
            'input' => [
                'projectId' => $projectId,
                'title' => $title,
            ]
        ]);

        if (isset($response->data->projectsV2AddDraftItem->item)) {
            return response()->json([
                'success' => true,
                'itemId' => $response->data->projectsV2AddDraftItem->item->id,
                'message' => 'Item added successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to add item',
            'errors' => $response->errors ?? []
        ], 400);
    }

    public function updateProjectItemField(string $organizationName, string $repositoryName, int $projectNumber)
    {
        $mutation = <<<'GRAPHQL'
        mutation ($input: UpdateProjectV2ItemFieldValueInput!) {
            updateProjectV2ItemFieldValue(input: $input) {
                projectV2Item {
                    id
                }
            }
        }
        GRAPHQL;

        $itemId = request()->input('itemId');
        $fieldId = request()->input('fieldId');
        $value = request()->input('value');

        error_log('Update field request - projectId: ' . request()->input('projectId') . ', itemId: ' . $itemId . ', fieldId: ' . $fieldId . ', value: ' . $value);

        $response = ApiHelper::githubGraphql($mutation, [
            'input' => [
                'projectId' => request()->input('projectId'),
                'itemId' => $itemId,
                'fieldId' => $fieldId,
                'value' => [
                    'singleSelectOptionId' => $value
                ]
            ]
        ]);

        error_log('Update field response: ' . json_encode($response));

        if (isset($response->data->updateProjectV2ItemFieldValue->projectV2Item)) {
            return response()->json([
                'success' => true,
                'message' => 'Item field updated successfully'
            ]);
        }

        // Return detailed error info
        $errorMsg = 'Failed to update item field';
        if (isset($response->errors) && !empty($response->errors)) {
            $errorMsg = $response->errors[0]->message ?? $errorMsg;
        }

        return response()->json([
            'success' => false,
            'message' => $errorMsg,
            'fullErrors' => $response->errors ?? [],
            'debugInfo' => [
                'projectId' => request()->input('projectId'),
                'itemId' => $itemId,
                'fieldId' => $fieldId,
                'value' => $value
            ]
        ], 400);
    }

    public function getProjectFields(string $organizationName, string $repositoryName, string $projectNumber)
    {
        $query = <<<'GRAPHQL'
        query ($org: String!, $number: Int!) {
            organization(login: $org) {
                projectV2(number: $number) {
                    id
                    field(name: "Status") {
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
            }
        }
        GRAPHQL;

        $response = ApiHelper::githubGraphql($query, [
            'org' => $organizationName,
            'number' => intval($projectNumber),
        ]);

        if (isset($response->data->organization->projectV2)) {
            $projectData = $response->data->organization->projectV2;
            return response()->json([
                'projectId' => $projectData->id,
                'field' => $projectData->field,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch project fields',
            'errors' => $response->errors ?? []
        ], 400);
    }

    public function addItemToProject(string $organizationName, string $repositoryName)
    {
        // The correct mutation for adding items to Projects V2
        $addMutation = <<<'GRAPHQL'
        mutation ($projectId: ID!, $contentId: ID!) {
            addProjectV2ItemById(input: {projectId: $projectId, contentId: $contentId}) {
                item {
                    id
                }
            }
        }
        GRAPHQL;

        $projectId = request()->input('projectId');
        $contentId = request()->input('contentId'); // Global node ID of issue/PR
        $fieldId = request()->input('fieldId'); // Status field ID
        $statusValue = request()->input('statusValue'); // The status option ID

        // First, add the item to the project
        $response = ApiHelper::githubGraphql($addMutation, [
            'projectId' => $projectId,
            'contentId' => $contentId,
        ]);

        // Debug: log the full response
        error_log('Add item response: ' . json_encode($response));

        if (!isset($response->data->addProjectV2ItemById->item)) {
            // If there are errors, return them with full detail
            if (isset($response->errors)) {
                error_log('GraphQL errors: ' . json_encode($response->errors));
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add item to project',
                    'errors' => $response->errors ?? [],
                    'errorDetails' => json_encode($response->errors ?? [])
                ], 400);
            }
            return response()->json([
                'success' => false,
                'message' => 'Failed to add item to project - no data returned',
                'response' => $response
            ], 400);
        }

        $itemId = $response->data->addProjectV2ItemById->item->id;

        // Now update the status field if provided
        if ($fieldId && $statusValue) {
            $updateMutation = <<<'GRAPHQL'
            mutation ($input: UpdateProjectV2ItemFieldValueInput!) {
                updateProjectV2ItemFieldValue(input: $input) {
                    projectV2Item {
                        id
                    }
                }
            }
            GRAPHQL;

            error_log('Updating status field - fieldId: ' . $fieldId . ', statusValue: ' . $statusValue);

            $updateResponse = ApiHelper::githubGraphql($updateMutation, [
                'input' => [
                    'projectId' => $projectId,
                    'itemId' => $itemId,
                    'fieldId' => $fieldId,
                    'value' => [
                        'singleSelectOptionId' => $statusValue
                    ]
                ]
            ]);

            error_log('Update status response: ' . json_encode($updateResponse));

            if (!isset($updateResponse->data->updateProjectV2ItemFieldValue->projectV2Item)) {
                // Status update failed - log the error
                $errorMsg = 'Failed to set status field';
                if (isset($updateResponse->errors)) {
                    error_log('Failed to set status field: ' . json_encode($updateResponse->errors));
                    $errorMsg = 'Item added but status update failed: ' . json_encode($updateResponse->errors[0]->message ?? 'Unknown error');
                }

                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                    'itemId' => $itemId,
                    'addedButStatusFailed' => true,
                    'errors' => $updateResponse->errors ?? []
                ], 400);
            }
        }

        return response()->json([
            'success' => true,
            'itemId' => $itemId,
            'message' => "Added to project successfully"
        ]);
    }
}
