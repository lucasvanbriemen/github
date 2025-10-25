<?php

namespace App\Http\Controllers;

use App\GithubConfig;
use App\Helpers\ApiHelper;
use App\Models\Issue;
use App\Models\Organization;
use App\Models\Repository;
use Illuminate\Http\Request;
use App\Models\IssueComment;
use App\Models\PullRequest;
use GrahamCampbell\GitHub\Facades\GitHub;

class IssueController extends Controller
{
    public function index($organizationName, $repositoryName, Request $request)
    {
        [$organization, $repository] = $this->getRepositoryWithOrganization($organizationName, $repositoryName);

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

        [$organization, $repository] = self::getRepositoryWithOrganization($organizationName, $repositoryName);

        $issue = Issue::where('repository_id', $repository->id)
            ->where('number', $issueNumber)
            ->with(['assignees', 'openedBy', 'comments' => function($query) {
                $query->with('author');
            }])
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

        [$organization, $repository] = self::getRepositoryWithOrganization($organizationName, $repositoryName);

        $issues = $repository->issues($state, $assignee)
            ->paginate(30);

        return view('repository.issue.list', [
            'organization' => $organization,
            'repository' => $repository,
            'issues' => $issues,
        ]);
    }

    public function resolveComment($organizationName, $repositoryName, $issueNumber, $commentId, Request $request)
    {
        $comment = IssueComment::where('id', $commentId)->firstOrFail();
        $comment->resolved = true;
        $comment->save();
        return response()->json(['resolved' => $comment->resolved]);
    }

    public function unresolveComment($organizationName, $repositoryName, $issueNumber, $commentId, Request $request)
    {
        $comment = IssueComment::where('id', $commentId)->firstOrFail();
        $comment->resolved = false;
        $comment->save();
        return response()->json(['resolved' => $comment->resolved]);
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

    public function getLinkedPullRequestsHtml($organizationName, $repositoryName, $issueNumber)
    {
        [$organization, $repository] = $this->getRepositoryWithOrganization($organizationName, $repositoryName);

        $issue = Issue::where('repository_id', $repository->id)
            ->where('number', $issueNumber)
            ->firstOrFail();

        $query ='
            query($owner: String!, $repo: String!, $number: Int!) {
                repository(owner: $owner, name: $repo) {
                    issue(number: $number) {
                        timelineItems(itemTypes: [CONNECTED_EVENT, DISCONNECTED_EVENT], first: 5) {
                            nodes {
                                ... on ConnectedEvent {
                                    subject {
                                        ... on PullRequest {
                                            databaseId
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        ';

        $variables = [
            'owner' => $organization ? $organization->name : $repository->owner_name,
            'repo' => $repository->name,
            'number' => $issue->number,
        ];

        $response = ApiHelper::githubGraphql($query, $variables);

        if (!$response || !isset($response->data->repository->issue)) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch linked pull requests'], 500);
        }

        $prIds = [];
        foreach ($response->data->repository->issue->timelineItems->nodes as $node) {
            if (isset($node->subject) && isset($node->subject->databaseId)) {
                $prIds[] = $node->subject->databaseId;
            }
        }

        $pullRequests = !empty($prIds) ? PullRequest::whereIn('id', $prIds)->get() : collect();

        return view('repository.issue.linked_pull_requests', [
            'organizationName' => $organizationName,
            'repositoryName' => $repositoryName,
            'issue' => $issue,
            'pullRequests' => $pullRequests,
        ]);
    }

    public function createIssue($organizationName, $repositoryName, Request $request)
    {
        GitHub::issue()->create(
            $organizationName,
            $repositoryName,
            [
                'title' => $request->input('title'),
                'body' => $request->input('body'),
                'assignees' => [GithubConfig::USERNAME],
            ]
        );

        return response()->json(['status' => 'success']);
    }

    public function listIssues($organizationName, $repositoryName)
    {
        [$organization, $repository] = $this->getRepositoryWithOrganization($organizationName, $repositoryName);

        $page = request()->query('page', 1);

        $issues = $repository
            ->issues()
            ->select(['id', 'title', 'state', 'labels', 'created_at', 'opened_by_id'])
            ->with([
                'openedBy:id,name,avatar_url',
                'assignees:id,name,avatar_url',
            ])
            ->paginate(50, ['*'], 'page', $page);

        $issues->getCollection()->transform(function ($issue) {
            $issue->created_at_human = $issue->created_at->diffForHumans();
            return $issue;
    });

        return response()->json($issues);
    }
}
