<?php

namespace App\Http\Controllers;

use App\Services\RepositoryService;
use App\Models\PullRequest;
use App\Models\Item;
use App\Models\PullRequestDetails;
use App\GithubConfig;
use App\Helpers\ApiHelper;
use App\Helpers\DiffRenderer;
use GrahamCampbell\GitHub\Facades\GitHub;

class PullRequestController extends Controller
{
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

        $response = GitHub::pullRequests()->create($organization->name, $repository->name, $prData);

        GitHub::issues()->update($organization->name, $repository->name, $response['number'], [
            'assignees' => $assignees,
        ]);

        $state = 'draft';
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

    public function update($organizationName, $repositoryName, $number)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        // If the posted data includes "draft" => false, we need to mark the PR as ready for review
        $changeDraft = request()->input('draft', null);
        if ($changeDraft !== null) {
            $pr = ApiHelper::githubApi("/repos/{$repository->full_name}/pulls/{$number}");
            $nodeId = $pr->node_id;
            $mutation = 'mutation {
                markPullRequestReadyForReview(input: {pullRequestId: "' . $nodeId . '"}) {
                    pullRequest {
                        isDraft
                    }
                }
            }';

            ApiHelper::githubGraphql($mutation);
        }

        $payload = [];
        foreach (request()->all() as $key => $value) {
            if (!in_array($key, ['state'])) {
                continue;
            }

            $payload[$key] = $value;
        }

        GitHub::pullRequests()->update($organizationName, $repositoryName, $number, $payload);

        return request()->all();
    }

    public static function getFiles($organizationName, $repositoryName, $number)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $pullRequest = PullRequest::where('repository_id', $repository->id)
           ->where('number', $number)
           ->firstOrFail();

        // For merged PRs, use merge_base_sha to preserve the original diff
        // For open/closed PRs, compare branches normally
        if ($pullRequest->state === 'merged' && $pullRequest->merge_base_sha && $pullRequest->head_sha) {
            // Compare from merge base to the head SHA at time of merge
            $url = "/repos/{$organization->name}/{$repository->name}/compare/{$pullRequest->merge_base_sha}...{$pullRequest->head_sha}";
        } else {
            // Compare branches for open/closed PRs
            $url = "/repos/{$organization->name}/{$repository->name}/compare/{$pullRequest->base_branch}...{$pullRequest->head_branch}";
        }

        $diff = ApiHelper::githubApi($url);

        $renderer = new DiffRenderer($diff);
        $files = $renderer->getFiles();
        return $files;
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

        GitHub::pullRequests()->reviewRequests()->create($organizationName, $repositoryName, $number, $toBeAdded[0]);
        GitHub::pullRequests()->reviewRequests()->remove($organizationName, $repositoryName, $number, $toBeRemoved);

        // Return current state and delta
        return response()->json([
            'currentReviewers' => $currentReviewers,
            'added' => $toBeAdded,
            'removed' => $toBeRemoved,
        ]);
    }

    public function submitReview($organizationName, $repositoryName, $number)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $number)
            ->firstOrFail();

        $commitSha = $item->getLatestCommitSha();

        $payload = [
            'body' => request()->input('body', ''),
            'event' => request()->input('state'),
            'comments' => request()->input('comments', []),
            'commit_id' => $commitSha,
        ];

        GitHub::pullRequests()->reviews()->create($organizationName, $repositoryName, $number, $payload);

        // Return success, webhook will sync the review data
        return response()->json(['success' => true], 200);
    }

    public function merge($organizationName, $repositoryName, $number)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);
        $item = Item::where('repository_id', $repository->id)
            ->where('number', $number)
            ->firstOrFail();


        $response = GitHub::pullRequests()->merge($organizationName, $repositoryName, $number, $item->title, $item->getLatestCommitSha(), 'merge');

        return response()->json([
            'number' => $number,
            'merged' => $response['merged'] ?? false,
            'message' => $response['message'] ?? '',
        ]);
    }
}
