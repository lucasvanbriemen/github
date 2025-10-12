<?php

namespace App\Http\Controllers;

use App\GithubConfig;
use App\Models\PullRequest;
use App\Helpers\ApiHelper;
use App\Helpers\DiffRenderer;
use App\Models\Organization;
use App\Models\Repository;
use App\Models\Issue;
use App\Models\ViewedFile;
use App\Models\Branch;
use App\Services\PullRequestCommentService;
use Illuminate\Http\Request;
use GrahamCampbell\GitHub\Facades\GitHub;

class PullRequestController extends Controller
{
    public function index($organizationName, $repositoryName, Request $request)
    {
        [$organization, $repository] = $this->getRepositoryWithOrganization($organizationName, $repositoryName);

        $branchesForNotice = $repository->branches()
            ->with('hasPullRequest')
            ->get()
            ->filter(function ($branch) {
                return $branch->showNotice();
            });

        return view('repository.pull_requests.index', [
            'organization' => $organization,
            'repository' => $repository,
            'branchesForNotice' => $branchesForNotice,
        ]);
    }

    public static function show($organizationName, $repositoryName, $pullRequestNumber)
    {
        // User repositories have "user" as organization name in the URL, while being null in the DB
        if ($organizationName === 'user') {
            $organizationName = null;
        }

        [$organization, $repository] = self::getRepositoryWithOrganization($organizationName, $repositoryName);

        $pullRequest = PullRequest::where('repository_id', $repository->id)
            ->where('number', $pullRequestNumber)
            ->with(['assignees', 'openedBy', 'comments', 'pullRequestComments.author', 'pullRequestReviews.user'])
            ->firstOrFail();

        // Process markdown to replace GitHub image URLs with proxy URLs
        $pullRequest->body = self::processMarkdownImages($pullRequest->body);

        // Get all comments using the service
        $commentService = app(PullRequestCommentService::class);
        $allComments = $commentService->getCommentsForDisplay($pullRequest);

        // Group comments by thread for better organization
        $allComments = $commentService->groupCommentsByThread($allComments);

        // Filter out comments where the body is null
        $allComments = $allComments->filter(function ($comment) {
            return $comment->body !== null;
        });

        // Process markdown in comments
        foreach ($allComments as $comment) {
            $comment->body = self::processMarkdownImages($comment->body);
            if (isset($comment->replies)) {
                foreach ($comment->replies as $reply) {
                    $reply->body = self::processMarkdownImages($reply->body);
                }
            }
        }

        return view('repository.pull_requests.show', [
            'organization' => $organization,
            'repository' => $repository,
            'pullRequest' => $pullRequest,
            'allComments' => $allComments,
        ]);
    }

    public static function showFiles($organizationName, $repositoryName, $pullRequestNumber)
    {
        // User repositories have "user" as organization name in the URL, while being null in the DB
        if ($organizationName === 'user') {
            $organizationName = null;
        }

        [$organization, $repository] = self::getRepositoryWithOrganization($organizationName, $repositoryName);

        $pullRequest = PullRequest::where('repository_id', $repository->id)
            ->where('number', $pullRequestNumber)
            ->firstOrFail();

        $diffString = self::getDiff($organizationName, $repositoryName, $pullRequestNumber);

        // Parse diff
        $renderer = new DiffRenderer($diffString);
        $files = $renderer->getFiles();

        // Get viewed files for this PR's branch
        $branch = Branch::where('name', $pullRequest->head_branch)
            ->where('repository_id', $pullRequest->repository_id)
            ->first();

        $viewedFiles = [];
        if ($branch) {
            $viewedFiles = ViewedFile::where('branch_id', $branch->id)
                ->where('viewed', true)
                ->pluck('file_path')
                ->toArray();
        }

        return view('repository.pull_requests.files', [
            'organization' => $organization,
            'repository' => $repository,
            'pullRequest' => $pullRequest,
            'files' => $files,
            'viewedFiles' => $viewedFiles,
        ]);
    }

    public static function getPullRequests($organizationName, $repositoryName, Request $request)
    {
        $state = $request->query('state', 'open');
        $assignee = $request->query('assignee', GithubConfig::USERID);

        [$organization, $repository] = self::getRepositoryWithOrganization($organizationName, $repositoryName);

        $pullRequests = $repository->pullRequests($state, $assignee)
            ->paginate(30);

        return view('repository.pull_requests.list', [
            'organization' => $organization,
            'repository' => $repository,
            'pullRequests' => $pullRequests,
        ]);
    }

    private static function getRepositoryWithOrganization($organizationName, $repositoryName)
    {
        $organization = null;

        if ($organizationName && $organizationName !== 'user') {
            $organization = Organization::where('name', $organizationName)->first();
        }

        $query = Repository::with('organization')->where('name', $repositoryName);

        if ($organization) {
            $query->where('organization_id', $organization->id);
        } else {
            $query->whereNull('organization_id');
        }

        $repository = $query->firstOrFail();

        return [$organization, $repository];
    }

    private static function processMarkdownImages($content)
    {
        if (!$content) {
            return $content;
        }

        // Replace markdown images: ![alt](url)
        $content = preg_replace_callback(
            '/!\[([^\]]*)\]\((https:\/\/(?:github\.com|raw\.githubusercontent\.com|user-images\.githubusercontent\.com)[^)]+)\)/',
            function ($matches) {
                $proxyUrl = route('image.proxy') . '?url=' . urlencode($matches[2]);
                return "![{$matches[1]}]({$proxyUrl})";
            },
            $content
        );

        // Replace HTML img tags: <img src="url">
        $content = preg_replace_callback(
            '/<img([^>]*\s+)?src=["\']?(https:\/\/(?:github\.com|raw\.githubusercontent\.com|user-images\.githubusercontent\.com)[^"\'>\s]+)["\']?([^>]*)>/i',
            function ($matches) {
                $proxyUrl = route('image.proxy') . '?url=' . urlencode($matches[2]);
                return "<img{$matches[1]}src=\"{$proxyUrl}\"{$matches[3]}>";
            },
            $content
        );

        return $content;
    }

    public function resolveComment($organizationName, $repositoryName, $pullRequestNumber, $commentId)
    {
        // User repositories have "user" as organization name in the URL, while being null in the DB
        if ($organizationName === 'user') {
            $organizationName = null;
        }

        [$organization, $repository] = $this->getRepositoryWithOrganization($organizationName, $repositoryName);

        $pullRequest = PullRequest::where('repository_id', $repository->id)
            ->where('number', $pullRequestNumber)
            ->firstOrFail();

        $comment = $pullRequest->pullRequestComments()->where('id', $commentId)->firstOrFail();
        $comment->resolved = true;
        $comment->save();

        return response()->json(['status' => 'success']);
    }

    public function unresolveComment($organizationName, $repositoryName, $pullRequestNumber, $commentId)
    {
        // User repositories have "user" as organization name in the URL, while being null in the DB
        if ($organizationName === 'user') {
            $organizationName = null;
        }

        [$organization, $repository] = $this->getRepositoryWithOrganization($organizationName, $repositoryName);

        $pullRequest = PullRequest::where('repository_id', $repository->id)
            ->where('number', $pullRequestNumber)
            ->firstOrFail();

        $comment = $pullRequest->pullRequestComments()->where('id', $commentId)->firstOrFail();
        $comment->resolved = false;
        $comment->save();

        return response()->json(['status' => 'success']);
    }

    public function resolveReviewComment($organizationName, $repositoryName, $pullRequestNumber, $commentId)
    {
        // User repositories have "user" as organization name in the URL, while being null in the DB
        if ($organizationName === 'user') {
            $organizationName = null;
        }

        [$organization, $repository] = $this->getRepositoryWithOrganization($organizationName, $repositoryName);

        $pullRequest = PullRequest::where('repository_id', $repository->id)
            ->where('number', $pullRequestNumber)
            ->firstOrFail();

        $comment = $pullRequest->pullRequestReviews()->where('id', $commentId)->firstOrFail();
        $comment->resolved = true;
        $comment->save();

        return response()->json(['status' => 'success']);
    }

    public function unresolveReviewComment($organizationName, $repositoryName, $pullRequestNumber, $commentId)
    {
        // User repositories have "user" as organization name in the URL, while being null in the DB
        if ($organizationName === 'user') {
            $organizationName = null;
        }

        [$organization, $repository] = $this->getRepositoryWithOrganization($organizationName, $repositoryName);

        $pullRequest = PullRequest::where('repository_id', $repository->id)
            ->where('number', $pullRequestNumber)
            ->firstOrFail();

        $comment = $pullRequest->pullRequestReviews()->where('id', $commentId)->firstOrFail();
        $comment->resolved = false;
        $comment->save();

        return response()->json(['status' => 'success']);
    }

    public static function getLinkedIssuesHtml($organizationName, $repositoryName, $pullRequestNumber)
    {
        [$organization, $repository] = self::getRepositoryWithOrganization($organizationName, $repositoryName);

        $pullRequest = PullRequest::where('repository_id', $repository->id)
            ->where('number', $pullRequestNumber)
            ->firstOrFail();

        $query = '
            query($owner: String!, $repo: String!, $number: Int!) {
                repository(owner: $owner, name: $repo) {
                    pullRequest(number: $number) {
                        closingIssuesReferences(first: 5) {
                            nodes {
                                databaseId
                            }
                        }
                    }
                }
            }
        ';

        $variables = [
            'owner' => $organization ? $organization->name : $repository->owner_name,
            'repo' => $repository->name,
            'number' => $pullRequest->number,
        ];

        $response = ApiHelper::githubGraphql($query, $variables);

        if (!$response || !isset($response->data->repository->pullRequest)) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch linked issues'], 500);
        }

        $issueIds = array_map(function ($node) {
            return $node->databaseId;
        }, $response->data->repository->pullRequest->closingIssuesReferences->nodes);

        $issues = Issue::whereIn('id', $issueIds)->get();

        return view('repository.pull_requests.linked_issues', [
            'organizationName' => $organizationName,
            'repositoryName' => $repositoryName,
            'issues' => $issues,
        ]);
    }

    public static function getDiff($organizationName, $repositoryName, $pullRequestNumber)
    {
        // User repositories have "user" as organization name in the URL, while being null in the DB
        if ($organizationName === 'user') {
            $organizationName = null;
        }

        [$organization, $repository] = self::getRepositoryWithOrganization($organizationName, $repositoryName);

        $pullRequest = PullRequest::where('repository_id', $repository->id)
            ->where('number', $pullRequestNumber)
            ->firstOrFail();

        $token = config('services.github.access_token');

        // Use the actual owner name from organization or repository
        $ownerName = $organization ? $organization->name : $repository->owner_name;

        // For merged PRs, use merge_base_sha to preserve the original diff
        // For open/closed PRs, compare branches normally
        if ($pullRequest->state === 'merged' && $pullRequest->merge_base_sha && $pullRequest->head_sha) {
            // Compare from merge base to the head SHA at time of merge
            $url = "https://api.github.com/repos/{$ownerName}/{$repositoryName}/compare/{$pullRequest->merge_base_sha}...{$pullRequest->head_sha}";
        } else {
            // Compare branches for open/closed PRs
            $url = "https://api.github.com/repos/{$ownerName}/{$repositoryName}/compare/{$pullRequest->base_branch}...{$pullRequest->head_branch}";
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/vnd.github.diff',
            'User-Agent: github-gui',
            'X-GitHub-Api-Version: 2022-11-28'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $diff = curl_exec($ch);
        curl_close($ch);

        return $diff;
    }

    public function updatePullRequest($organizationName, $repositoryName, $pullRequestNumber, Request $request)
    {
        $data = [
            'title' => $request->title,
            'body' => $request->body,
        ];

        GitHub::pulls()->update($organizationName, $repositoryName, $pullRequestNumber, $data);
    }

    public function mergePullRequest($organizationName, $repositoryName, $pullRequestNumber, Request $request)
    {
        // Get the pull request details to obtain the HEAD SHA
        $pullRequestData = GitHub::pulls()->show($organizationName, $repositoryName, $pullRequestNumber);

        $commitMessage = $request->input('message', 'Merged pull request');
        $sha = $pullRequestData['head']['sha'];
        $mergeMethod = $request->input('merge_method', 'merge'); // merge, squash, or rebase

        GitHub::pulls()->merge($organizationName, $repositoryName, $pullRequestNumber, $commitMessage, $sha, $mergeMethod);
    }

    public function closePullRequest($organizationName, $repositoryName, $pullRequestNumber)
    {
        GitHub::pulls()->update($organizationName, $repositoryName, $pullRequestNumber, [
            'state' => 'closed'
        ]);
    }

    public function fileViewed($organizationName, $repositoryName, $pullRequestNumber, Request $request)
    {
        [$organization, $repository] = $this->getRepositoryWithOrganization($organizationName, $repositoryName);

        $pullRequest = PullRequest::where('repository_id', $repository->id)
            ->where('number', $pullRequestNumber)
            ->firstOrFail();

        $filePath = $request->query('file');
        
         // Ensure the branch exists
        $branch = Branch::firstOrCreate(
            [
                'name' => $pullRequest->head_branch,
                'repository_id' => $pullRequest->repository_id,
            ]
        );

        
        ViewedFile::updateOrCreate(
            [
                'branch_id' => $branch->id,
                'file_path' => $filePath,
            ],
            ['viewed' => true]
        );

        return response()->json(['status' => 'success']);
    }

    public function fileNotViewed($organizationName, $repositoryName, $pullRequestNumber, Request $request)
    {
        [$organization, $repository] = $this->getRepositoryWithOrganization($organizationName, $repositoryName);

        $pullRequest = PullRequest::where('repository_id', $repository->id)
            ->where('number', $pullRequestNumber)
            ->firstOrFail();

        $filePath = $request->query('file');
        
        // Ensure the branch exists
        $branch = Branch::firstOrCreate(
            [
                'name' => $pullRequest->head_branch,
                'repository_id' => $pullRequest->repository_id,
            ]
        );

        ViewedFile::where('branch_id', $branch->id)
            ->where('file_path', $filePath)
            ->delete();

        return response()->json(['status' => 'success']);
    }

    public function addComment($organizationName, $repositoryName, $pullRequestNumber, Request $request)
    {
        // Create an issue comment
        GitHub::issues()->comments()->create(
            $organizationName,
            $repositoryName,
            $pullRequestNumber,
            ['body' => $request->input('body')]
        );

        return response()->json(['status' => 'success']);
    }

    public function addPRComment($organizationName, $repositoryName, $pullRequestNumber, Request $request)
    {
        // Use direct API call because knplabs/github-api doesn't support the newer line+side format
        // GitHub API v3 supports: line + side (newer) instead of position (deprecated)

        $token = config('services.github.access_token');
        $url = "https://api.github.com/repos/{$organizationName}/{$repositoryName}/pulls/{$pullRequestNumber}/comments";

        $data = [
            'body' => $request->input('body'),
            'commit_id' => $request->input('commit_id'),
            'path' => $request->input('filePath'),
            'line' => (int) $request->input('line'),
            'side' => $request->input('side'),
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/vnd.github+json',
            'Content-Type: application/json',
            'User-Agent: github-gui',
            'X-GitHub-Api-Version: 2022-11-28'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'error', 'message' => $response], $httpCode);
        }
    }
}
