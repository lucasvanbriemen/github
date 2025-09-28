<?php

namespace App\Http\Controllers;

use App\GithubConfig;
use App\Models\PullRequest;
use App\Models\Issue;
use App\Models\Organization;
use App\Models\Repository;
use App\Services\PullRequestCommentService;
use Illuminate\Http\Request;

class PullRequestController extends Controller
{
    public function index($organizationName, $repositoryName, Request $request)
    {
        [$organization, $repository] = $this->getRepositoryWithOrganization($organizationName, $repositoryName);

        return view('repository.pull_requests.index', [
            'organization' => $organization,
            'repository' => $repository,
        ]);
    }

    public static function show($organizationName, $repositoryName, $pullRequestNumber)
    {
        // User repositories have "user" as organization name in the URL, while being null in the DB
        if ($organizationName === 'user') {
            $organizationName = null;
        }

        [$organization, $repository] = self::getRepositoryWithOrganization($organizationName, $repositoryName);

        $pullRequest = PullRequest::where('repository_id', $repository->github_id)
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
            $query->where('organization_id', $organization->github_id);
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
}
