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
                    }
                }
            }
        }
        GRAPHQL;

        $response = ApiHelper::githubGraphql($mutation, ['owner' => $organizationName, 'name' => $repositoryName,]);
        $data = $response->data->repository->projectsV2->nodes ?? [];

        $projects = [];
        foreach ($data as $project) {
            // Get the status field and its options
            $fields = $project->fields->nodes;
            foreach ($fields as $field) {
                if (isset($field->name) && $field->name === 'Status') {
                    $project->statusOptions = $field->options;
                    break;
                }
            }

            $projects[] = [
                'id' => $project->id,
                'title' => $project->title,
                'number' => $project->number,
                'status_options' => $project->statusOptions ?? [],
                'updated_at' => Carbon::parse($project->updatedAt)->diffForHumans(),
            ];
        }

        return response()->json($projects);
    }

    public function getProjectFields(string $organizationName, string $repositoryName, int $projectNumber)
    {
        $query = <<<'GRAPHQL'
        query ($org: String!, $num: Int!) {
            organization(login: $org) {
                projectV2(number: $num) {
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
            'num' => (int) $projectNumber,
        ]);

        if (isset($response->data->organization->projectV2)) {
            $projectV2 = $response->data->organization->projectV2;
            return response()->json([
                'projectId' => $projectV2->id,
                'field' => $projectV2->field ?? null
            ]);
        }

        return response()->json([
            'projectId' => null,
            'field' => null
        ]);
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

        $columns = collect(
            $projectData->field->options
        )->mapWithKeys(fn ($option) => [
            $option->name => [
                'name' => $option->name,
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

            $column['items'][] = $DBitems->get($item->content->number);

            $columns->put($columnName, $column);
        }

        return response()->json(array_values($columns->toArray()));
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

        return response()->json([
            'success' => true,
            'message' => 'Item field updated successfully'
        ]);
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
        }

        return response()->json([
            'success' => true,
            'itemId' => $itemId,
            'message' => "Added to project successfully"
        ]);
    }

    public function updateItemProjectStatus(string $organizationName, string $repositoryName)
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

        $projectId = request()->input('projectId');
        $itemId = request()->input('itemId');
        $fieldId = request()->input('fieldId');
        $statusValue = request()->input('statusValue');

        error_log('Update item project status - projectId: ' . $projectId . ', itemId: ' . $itemId . ', fieldId: ' . $fieldId . ', statusValue: ' . $statusValue);

        $response = ApiHelper::githubGraphql($mutation, [
            'input' => [
                'projectId' => $projectId,
                'itemId' => $itemId,
                'fieldId' => $fieldId,
                'value' => [
                    'singleSelectOptionId' => $statusValue
                ]
            ]
        ]);

        if (isset($response->data->updateProjectV2ItemFieldValue->projectV2Item)) {
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully'
            ]);
        }

        $errorMsg = 'Failed to update status';
        if (isset($response->errors) && !empty($response->errors)) {
            $errorMsg = $response->errors[0]->message ?? $errorMsg;
        }

        return response()->json([
            'success' => false,
            'message' => $errorMsg,
            'fullErrors' => $response->errors ?? []
        ], 400);
    }

    public function removeItemFromProject(string $organizationName, string $repositoryName)
    {
        $mutation = <<<'GRAPHQL'
        mutation ($input: DeleteProjectV2ItemInput!) {
            deleteProjectV2Item(input: $input) {
                deletedItemId
            }
        }
        GRAPHQL;

        $projectId = request()->input('projectId');
        $itemId = request()->input('itemId');

        error_log('Remove item from project - projectId: ' . $projectId . ', itemId: ' . $itemId);

        $response = ApiHelper::githubGraphql($mutation, [
            'input' => [
                'projectId' => $projectId,
                'itemId' => $itemId
            ]
        ]);

        if (isset($response->data->deleteProjectV2Item->deletedItemId)) {
            return response()->json([
                'success' => true,
                'message' => 'Item removed from project successfully'
            ]);
        }

        $errorMsg = 'Failed to remove item from project';
        if (isset($response->errors) && !empty($response->errors)) {
            $errorMsg = $response->errors[0]->message ?? $errorMsg;
        }

        return response()->json([
            'success' => false,
            'message' => $errorMsg,
            'fullErrors' => $response->errors ?? []
        ], 400);
    }
}
