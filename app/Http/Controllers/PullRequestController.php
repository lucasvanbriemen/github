<?php

namespace App\Http\Controllers;

use App\Services\RepositoryService;
use App\Models\PullRequest;
use App\Models\GithubUser;
use App\Models\Item;
use App\Helpers\DiffRenderer;
use App\GithubConfig;
use App\Helpers\ApiHelper;
use Illuminate\Support\Facades\DB;
use GrahamCampbell\GitHub\Facades\Github;

class PullRequestController extends Controller
{
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

        return response()->json([
            'branches' => $branchNames,
            'assignees' => $assignees,
            'default_assignee' => $default_assignee,
            'master_branch' => $master_branch,
        ]);
    }

    public function create($organizationName, $repositoryName)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $assigneeInput = request()->input('assignee');
        $assignees[] = $assigneeInput;

        $prData = [
            'title' => request()->input('title'),
            'head' => request()->input('head_branch'),
            'base' => request()->input('base_branch'),
            'body' => request()->input('body', ''),
            'draft' => true,
        ];

        $response = Github::pullRequests()->create($organization->name, $repository->name, $prData);

        Github::issues()->update($organization->name, $repository->name, $response['number'], [
            'assignees' => $assignees,
        ]);

        $state = $response['state'] ?? 'open';
        // Determine merge base sha for accurate diffing
        $mergeBaseSha = null;
        $headSha = $response['head']['sha'] ?? null;
        $baseRef = $response['base']['ref'] ?? null;
        $headRef = $response['head']['ref'] ?? null;

        if ($headSha && $baseRef && $headRef) {
            $compareData = ApiHelper::githubApi("/repos/{$organization->name}/{$repository->name}/compare/{$baseRef}...{$headRef}");
            if ($compareData && isset($compareData->merge_base_commit->sha)) {
                $mergeBaseSha = $compareData->merge_base_commit->sha;
            }
        }

        // Persist base fields in items table
        $pr = PullRequest::updateOrCreate(
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

        // Persist PR-specific fields in pull_requests table
        DB::table('pull_requests')->updateOrInsert(
            ['id' => $response['id']],
            [
                'head_branch' => $headRef,
                'head_sha' => $headSha,
                'base_branch' => $baseRef,
                'merge_base_sha' => $mergeBaseSha,
                'updated_at' => now(),
            ]
        );

        // Sync assignees (uses issue_assignees table)
        $assigneeGithubIds = [];
        if (!empty($response['assignees']) && is_array($response['assignees'])) {
            foreach ($response['assignees'] as $assignee) {
                if (is_array($assignee) && isset($assignee['id'])) {
                    $assigneeGithubIds[] = $assignee['id'];
                    GithubUser::updateFromWebhook((object) $assignee);
                }
            }
        } elseif (!empty($response['assignee']) && is_array($response['assignee']) && isset($response['assignee']['id'])) {
            // GitHub may return a single assignee
            $assigneeGithubIds[] = $response['assignee']['id'];
            GithubUser::updateFromWebhook((object) $response['assignee']);
        }
        if ($pr) {
            $pr->assignees()->sync($assigneeGithubIds);
        }

        return response()->json([
            'number' => $response['number'] ?? null,
            'state' => $state,
        ]);
    }
}
