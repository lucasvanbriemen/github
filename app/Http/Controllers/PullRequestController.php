<?php

namespace App\Http\Controllers;

use App\Services\RepositoryService;
use App\Models\PullRequest;
use App\Models\PullRequestDetails;
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
        $baseSha = $response['base']['sha'] ?? null;
        $baseRef = $response['base']['ref'] ?? null;
        $headRef = $response['head']['ref'] ?? null;

        // Prefer comparing by SHAs (stable even if branches move/delete)
        if ($headSha && $baseSha) {
            $compareData = ApiHelper::githubApi("/repos/{$organization->name}/{$repository->name}/compare/{$baseSha}...{$headSha}");
        } elseif ($headRef && $baseRef) {
            $compareData = ApiHelper::githubApi("/repos/{$organization->name}/{$repository->name}/compare/{$baseRef}...{$headRef}");
        } else {
            $compareData = null;
        }
        if ($compareData && isset($compareData->merge_base_commit->sha)) {
            $mergeBaseSha = $compareData->merge_base_commit->sha;
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

        PullRequestDetails::updateOrCreate(
            ['id' => $response['id']],
            [
                'head_branch' => $headRef,
                'head_sha' => $headSha,
                'base_branch' => $baseRef,
                'merge_base_sha' => $mergeBaseSha,
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
        
        $pr->assignees()->sync($assigneeGithubIds);

        return response()->json([
            'number' => $response['number'] ?? null,
            'state' => $state,
        ]);
    }

    function merge($organizationName, $repositoryName, $number)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $pr = PullRequest::where('repository_id', $repository->id)->where('number', $number)->first();
        $headSha = $pr->details->head_sha;

        $response = Github::pullRequests()->merge($organization->name, $repository->name, $number, "Merge {$pr->title}",$headSha);

        return response()->json($response);
    }
}
