<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Services\RepositoryService;
use App\Models\Issue;
use App\Models\Commit;
use App\Helpers\ApiHelper;
use App\GithubConfig;
use GrahamCampbell\GitHub\Facades\GitHub;

class ItemController extends Controller
{
    public function index($organizationName, $repositoryName, $type)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $state = request()->query('state', 'open');
        $assignee = request()->query('assignee', 'any');
        $search = request()->query('search', '');

        $query = $repository->items($type, $state, $assignee, $search)
            ->select(['id', 'title', 'state', 'labels', 'created_at', 'opened_by_id', 'number', 'type'])
            ->with([
                'openedBy:id,display_name,avatar_url',
                'assignees:id,name,avatar_url',
            ]);

        $page = request()->query('page', 1);
        $items = $query->paginate(30, ['*'], 'page', $page);

        return response()->json($items);
    }

    public function getLinkedItems($organizationName, $repositoryName, $number)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $number)
            ->firstOrFail();

        $type = "issue";
        if ($item->type !== 'issue') {
            $type = "pullRequest";
        }

        $query = '
            query ($org: String!, $repo: String!, $number: Int!) {
            repository(owner: $org, name: $repo) {
                ' . $type . '(number: $number) {
                timelineItems(
                    first: 100
                    itemTypes: [CONNECTED_EVENT, CROSS_REFERENCED_EVENT, REFERENCED_EVENT]
                ) {
                    nodes {
                    __typename

                    ... on ConnectedEvent {
                        subject {
                        __typename
                        ... on Issue { number title url }
                        ... on PullRequest { number title url }
                        }
                    }

                    ... on CrossReferencedEvent {
                        source {
                        __typename
                        ... on Issue { number title url }
                        ... on PullRequest { number title url }
                        }
                    }

                    ... on ReferencedEvent {
                        subject {
                        __typename
                        ... on Issue { number title url }
                        ... on PullRequest { number title url }
                        }
                    }
                    }
                }
                }
            }
        }';

        $variables = [
            'org' => $organizationName,
            'repo' => $repositoryName,
            'number' => (int) $number,
        ];

        $response = ApiHelper::githubGraphql($query, $variables);

        $ids = [];
        foreach ($response->data->repository->{$type}->timelineItems->nodes as $node) {
            if (isset($node->subject)) {
                $ids[] = $node->subject->number;
            } elseif (isset($node->source)) {
                $ids[] = $node->source->number;
            }
        }

        $items = Item::where('repository_id', $repository->id)->whereIn('number', $ids)->select(['id', 'title', 'state', 'number', 'type', 'created_at'])->get();

        foreach ($items as $item) {
            $type = $item->isPullRequest() ? 'pulls' : 'issues';
            $item->url = "#/{$organizationName}/{$repositoryName}/{$type}/{$item->number}";
        }

        return response()->json($items);
    }

    public function create($organizationName, $repositoryName)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $assigneeInput = request()->input('assignee');
        $assignees[] = $assigneeInput;

        $prData = [
            'title' => request()->input('title'),
            'body' => request()->input('body', ''),
            'draft' => true,
        ];

        $response = GitHub::issues()->create($organization->name, $repository->name, $prData);

        GitHub::issues()->update($organization->name, $repository->name, $response['number'], [
            'assignees' => $assignees,
        ]);

        $state = $response['state'] ?? 'open';

        // Persist base fields in items table
        $issue = Issue::updateOrCreate(
            ['id' => $response['id']],
            [
                'repository_id' => $repository->id,
                'number' => $response['number'] ?? null,
                'title' => $response['title'] ?? '',
                'body' => $response['body'] ?? '',
                'state' => $state,
                'labels' => json_encode($response['labels'] ?? []),
                'opened_by_id' => $response['user']['id'] ?? null,
            ]
        );

        // Sync assignees (uses issue_assignees table)
        $assigneeGithubIds = [];
        if (!empty($response['assignees']) && is_array($response['assignees'])) {
            foreach ($response['assignees'] as $assignee) {
                $assigneeGithubIds[] = $assignee['id'];
            }
        } elseif (!empty($response['assignee']) && is_array($response['assignee']) && isset($response['assignee']['id'])) {
            // GitHub may return a single assignee
            $assigneeGithubIds[] = $response['assignee']['id'];
        }

        $issue->assignees()->sync($assigneeGithubIds);

        return response()->json([
            'number' => $response['number'] ?? null,
            'state' => $state,
        ]);
    }

    public static function show($organizationName, $repositoryName, $issueNumber)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $issueNumber)
            ->with([
                'assignees',
                'openedBy',
                'comments'
            ])
            ->firstOrFail();

        foreach ($item->comments as $comment) {
            self::formatComments($comment);
        }

        // If its a PR we also want to load that specific data
        if ($item->isPullRequest()) {
            $item->load([
                'details',
                'requestedReviewers.user'
            ]);

            // Load the latest commit with workflow information
            $latestSha = $item->getLatestCommitSha();
            $item->latest_commit = Commit::where('sha', $latestSha)->with('workflow')->first();
        }

        // Fetch the global node ID from GitHub and what projects it's in
        $type = $item->isPullRequest() ? 'pullRequest' : 'issue';
        $query = "
            query (\$owner: String!, \$name: String!, \$number: Int!) {
                repository(owner: \$owner, name: \$name) {
                    $type(number: \$number) {
                        id
                        projectItems(first: 10) {
                            nodes {
                                id
                                project {
                                    id
                                    title
                                    number
                                }
                                fieldValueByName(name: \"Status\") {
                                    ... on ProjectV2ItemFieldSingleSelectValue {
                                        name
                                    }
                                }
                            }
                        }
                    }
                }
            }
        ";

        $response = ApiHelper::githubGraphql($query, [
            'owner' => $organizationName,
            'name' => $repository->name,
            'number' => (int) $issueNumber,
        ]);

        if (isset($response->data->repository->{$type}->id)) {
            $item->node_id = $response->data->repository->{$type}->id;

            // Add the projects this item is in and fetch their field options
            $projects = [];
            if (isset($response->data->repository->{$type}->projectItems->nodes)) {
                foreach ($response->data->repository->{$type}->projectItems->nodes as $projectItem) {
                    $projectData = [
                        'id' => $projectItem->project->id,
                        'title' => $projectItem->project->title,
                        'number' => $projectItem->project->number,
                        'itemId' => $projectItem->id,
                        'status' => $projectItem->fieldValueByName->name ?? null
                    ];

                    // Fetch the Status field options for this project
                    $fieldsQuery = "
                        query (\$org: String!, \$num: Int!) {
                            organization(login: \$org) {
                                projectV2(number: \$num) {
                                    field(name: \"Status\") {
                                        ... on ProjectV2SingleSelectField {
                                            id
                                            options {
                                                id
                                                name
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    ";

                    $fieldsResponse = ApiHelper::githubGraphql($fieldsQuery, [
                        'org' => $organizationName,
                        'num' => (int) $projectItem->project->number,
                    ]);

                    if (isset($fieldsResponse->data->organization->projectV2->field)) {
                        $field = $fieldsResponse->data->organization->projectV2->field;
                        $projectData['status_field_id'] = $field->id ?? null;
                        $projectData['options'] = $field->options ?? [];
                    }

                    $projects[] = $projectData;
                }
            }
            $item->projects_v2 = $projects;
        }

        return response()->json($item);
    }

    private static function formatComments($comment)
    {
        if ($comment->type === 'review') {
            $comment->details = $comment->reviewDetails;
        }

        if ($comment->type === 'code') {
            $comment->details = $comment->commentDetails;
        }

        if ($comment->type === 'issue') {
            return;
        }

        $comment->child_comments = $comment->details->childComments ?? [];

        self::formatChildComments($comment);

        unset($comment->reviewDetails);
        unset($comment->commentDetails);
        unset($comment->user_id);
        unset($comment->issue_id);

        if ($comment->details) {
            unset($comment->details->childComments);
            unset($comment->details->base_comment_id);
            unset($comment->details->created_at);
            unset($comment->details->updated_at);
        }
    }

    private static function formatChildComments($parentComment)
    {
        $childComments = $parentComment->child_comments ?? $parentComment->childComments ?? [];

        if (!$childComments) {
            return;
        }

        foreach ($childComments as $childComment) {
            // Child comments get author and body from baseComment
            if ($childComment->baseComment) {
                unset($childComment->author);
                unset($childComment->type);

                $childComment->author = $childComment->baseComment->author;
                $childComment->type = $childComment->baseComment->type;
                $childComment->body = $childComment->baseComment->body ?? '';
                $childComment->resolved = $childComment->baseComment->resolved;

                unset($childComment->baseComment);
            }

            $childComment->id = $childComment->base_comment_id;

            unset($childComment->base_comment_id);
            unset($childComment->details);
            unset($childComment->reviewDetails);
            unset($childComment->commentDetails);
            unset($childComment->details);

            // Recursively format grandchild comments
            self::formatChildComments($childComment);
        }
    }

    public function update($organizationName, $repositoryName, $number)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $number)
            ->firstOrFail();

        $data = request()->only(['body']);

        $item->update($data);

        // We also need to update the item body on GitHub
        GitHub::issues()->update($organization->name, $repository->name, $number, [
            'body' => $item->body,
        ]);

        return response()->json($item);
    }

    public function metadata($organizationName, $repositoryName)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $branches = $repository->branches()->get();
        $branchNames = $branches->pluck('name');

        $assignees = $repository->contributors()->with('githubUser')->get()->map(function ($contributor) {
            return $contributor->githubUser;
        });

        $master_branch = $repository->master_branch;
        $default_assignee = GithubConfig::USERNAME;

        $templatesPath = resource_path('repository_templates/templates.json');
        $templatesJson = file_get_contents($templatesPath);
        $templates = json_decode($templatesJson, true);

        return response()->json([
           'branches' => $branchNames,
           'assignees' => $assignees,
           'default_assignee' => $default_assignee,
           'master_branch' => $master_branch,
           'templates' => $templates,
        ]);
    }
}
