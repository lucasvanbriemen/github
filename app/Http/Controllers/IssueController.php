<?php

namespace App\Http\Controllers;

use App\GithubConfig;
use App\Helpers\ApiHelper;
use App\Models\Issue;
use App\Models\Organization;
use App\Models\Repository;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    public function index($organizationName, $repositoryName, Request $request)
    {
        $organization = Organization::where('name', $organizationName)->first();

        $query = Repository::where('name', $repositoryName);
        if ($organization) {
            $query->where('organization_id', $organization->github_id);
        }

        $repository = $query->firstOrFail();

        return view('repository.issue.issues', [
            'organization' => $organization,
            'repository' => $repository,
        ]);
    }

    public static function show($organizationName, $repositoryName, $issueNumber)
    {
        // User repositories have "user" as organization name in the URL, while being null in the DB
        if ($organizationName === 'user') {
            $organizationName = null;
        }

        $organization = Organization::where('name', $organizationName)->first();

        $query = Repository::where('name', $repositoryName);
        if ($organization) {
            $query->where('organization_id', $organization->github_id);
        }

        $repository = $query->firstOrFail();

        $issue = Issue::where('repository_id', $repository->github_id)
            ->where('number', $issueNumber)
            ->with('assignees', 'openedBy', 'comments.author')
            ->firstOrFail();

        // Process markdown to replace GitHub image URLs with proxy URLs
        $issue->body = self::processMarkdownImages($issue->body);

        foreach ($issue->comments as $comment) {
            $comment->body = self::processMarkdownImages($comment->body);
        }

        return view('repository.issue.issue', [
            'organization' => $organization,
            'repository' => $repository,
            'issue' => $issue,
        ]);
    }

    public static function getIssues($organizationName, $repositoryName, Request $request)
    {
        $state = $request->query('state', 'open');
        $assignee = $request->query('assignee', GithubConfig::USERID);

        $organization = Organization::where('name', $organizationName)->first();

        $query = Repository::where('name', $repositoryName);
        if ($organization) {
            $query->where('organization_id', $organization->github_id);
        }
        $repository = $query->firstOrFail();

        $issues = $repository->issues($state, $assignee)
            ->paginate(30);

        return view('repository.issue.list', [
            'organization' => $organization,
            'repository' => $repository,
            'issues' => $issues,
        ]);
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
