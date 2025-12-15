<?php

namespace App\Http\Controllers;

use App\Services\RepositoryService;
use App\Models\PullRequest;
use App\Models\Item;
use App\Models\PullRequestDetails;
use App\GithubConfig;
use App\Helpers\ApiHelper;

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

        $response = ApiHelper::githubApi("/repos/{$organization->name}/{$repository->name}/pulls", 'POST', $prData);

        ApiHelper::githubApi("/repos/{$organization->name}/{$repository->name}/issues/{$response->number}", 'PATCH', [
            'assignees' => $assignees,
        ]);

        $state = $response->state ?? 'draft';
        // Determine merge base sha for accurate diffing
        $mergeBaseSha = null;
        $headSha = $response->head->sha ?? null;
        $baseSha = $response->base->sha ?? null;
        $baseRef = $response->base->ref ?? null;
        $headRef = $response->head->ref ?? null;

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
            ['id' => $response->id],
            [
                'repository_id' => $repository->id,
                'number' => $response->number ?? null,
                'title' => $response->title ?? '',
                'body' => $response->body ?? '',
                'state' => $state,
                'labels' => json_encode($response->labels ?? []),
                'opened_by_id' => $response->user->id ?? null,
            ]
        );

        PullRequestDetails::updateOrCreate(
            ['id' => $response->id],
            [
                'head_branch' => $headRef,
                'head_sha' => $headSha,
                'base_branch' => $baseRef,
                'merge_base_sha' => $mergeBaseSha,
            ]
        );

        // Sync assignees (uses issue_assignees table)
        $assigneeGithubIds = [];
        if (!empty($response->assignees) && is_array($response->assignees)) {
            foreach ($response->assignees as $assignee) {
                $assigneeGithubIds[] = $assignee->id;
            }
        } elseif (!empty($response->assignee) && is_object($response->assignee) && isset($response->assignee->id)) {
            // GitHub may return a single assignee
            $assigneeGithubIds[] = $response->assignee->id;
        }

        $pr->assignees()->sync($assigneeGithubIds);

        return response()->json([
            'number' => $response->number ?? null,
            'state' => $state,
        ]);
    }

    public function update($organizationName, $repositoryName, $number)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        if (request()->has('draft')) {
            $isDraft = request()->input('draft');

            // Get the PR to find its node ID
            $pr = ApiHelper::githubApi("/repos/{$organization->name}/{$repository->name}/pulls/{$number}");
            $nodeId = $pr->node_id;

            if ($isDraft) {
                // Convert to draft
                $mutation = 'mutation {
                    convertPullRequestToDraft(input: {pullRequestId: "'.$nodeId.'"}) {
                        pullRequest {
                            isDraft
                        }
                    }
                }';
            } else {
                // Mark ready for review
                $mutation = 'mutation {
                    markPullRequestReadyForReview(input: {pullRequestId: "'.$nodeId.'"}) {
                        pullRequest {
                            isDraft
                        }
                    }
                }';
            }

            ApiHelper::githubGraphql($mutation);

            return request()->all();
        }

        ApiHelper::githubApi("/repos/{$organization->name}/{$repository->name}/pulls/{$number}", 'PATCH', request()->all());

        return request()->all();
    }

    public function requestReviewers($organizationName, $repositoryName, $number)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $pr = Item::with('requestedReviewers.user')
          ->where('repository_id', $repository->id)
          ->where('number', $number)
          ->firstOrFail();

        $currentReviewers = $pr->requestedReviewers
            ->where('state', 'pending')
            ->pluck('user.login')
            ->all();
        $updatedReviewers = request()->input('reviewers', []);

        $toBeAdded = [];
        $toBeRemoved = [];

        // Determine which reviewers need to be added (in input but not currently assigned)
        foreach ($updatedReviewers as $updatedReviewer) {
            if (!in_array($updatedReviewer, $currentReviewers)) {
                $toBeAdded[] = $updatedReviewer; // push individual reviewer
            }
        }

        // Determine which reviewers need to be removed (currently assigned but not in input)
        foreach ($currentReviewers as $currentReviewer) {
            if (!in_array($currentReviewer, $updatedReviewers)) {
                $toBeRemoved[] = $currentReviewer;
            }
        }

        // Sync with GitHub API:
        if (!empty($toBeAdded)) {
            ApiHelper::githubApi("/repos/{$organization->name}/{$repository->name}/pulls/{$number}/requested_reviewers", 'POST', [
                'reviewers' => $toBeAdded
            ]);
        }
        if (!empty($toBeRemoved)) {
            ApiHelper::githubApi("/repos/{$organization->name}/{$repository->name}/pulls/{$number}/requested_reviewers", 'DELETE', [
                'reviewers' => $toBeRemoved
            ]);
        }

        // Return current state and delta
        return response()->json([
            'currentReviewers' => $currentReviewers,
            'added' => $toBeAdded,
            'removed' => $toBeRemoved,
        ]);
    }
}
