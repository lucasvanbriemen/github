<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Services\RepositoryService;
use App\Models\PullRequest;
use App\Helpers\DiffRenderer;
use App\Helpers\ApiHelper;

class ItemController extends Controller
{
    public function index($organizationName, $repositoryName, $type)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $state = request()->query('state', 'open');
        $assignee = request()->query('assignee', 'any');
        
        $query = $repository->items($type, $state, $assignee)
            ->select(['id', 'title', 'state', 'labels', 'created_at', 'opened_by_id', 'number'])
            ->with([
                'openedBy:id,display_name,avatar_url',
                'assignees:id,name,avatar_url',
            ]);
            
        $page = request()->query('page', 1);
        $items = $query->paginate(30, ['*'], 'page', $page);

        $items->getCollection()->transform(function ($item) {
            $item->created_at_human = $item->created_at->diffForHumans();
            return $item;
        });

        return response()->json($items);
    }

    public static function show($organizationName, $repositoryName, $issueNumber)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $issueNumber)
            ->with(['assignees', 'openedBy', 'comments' => function($query) {
                $query->with('author');
            }])
            ->firstOrFail();
        
        $item->body = self::processMarkdownImages($item->body);
        $item->created_at_human = $item->created_at->diffForHumans();
        foreach ($item->comments as $comment) {
            $comment->body = self::processMarkdownImages($comment->body);
            $comment->created_at_human = $comment->created_at->diffForHumans();
        }

        // If its a PR we also want to load that specific data
        if ($item->isPullRequest()) {
            self::loadPullRequestData($item);
        }

        return response()->json($item);
    }

    // For a private repo, we need to proxy images through our server instead of using the normal link
    // As you need to be authenticated to view them
    // So we use a proxy route to fetch and serve the images 
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
                return "<br><img{$matches[1]}src=\"{$proxyUrl}\"{$matches[3]}>";
            },
            $content
        );

        return $content;
    }

    private static function loadPullRequestData($item)
    {
        // Load PR-specific details (branches, SHAs, etc.)
        $item->load([
            'details',
            'requestedReviewers.user',
            'pullRequestReviews' => function($query) {
                $query->with('user')->orderBy('created_at', 'asc');
                $query->with('childComments');
            }
        ]);

        // Process PR reviews markdown images
        foreach ($item->pullRequestReviews as $review) {
            if ($review->body) {
                $review->body = self::processMarkdownImages($review->body);
            }
            $review->created_at_human = $review->created_at->diffForHumans();

            foreach ($review->childComments as $comment) {
                $comment->body = self::processMarkdownImages($comment->body);
                $comment->created_at_human = $comment->created_at->diffForHumans();

                foreach ($comment->childComments as $reply) {
                    $reply->body = self::processMarkdownImages($reply->body);
                    $reply->created_at_human = $reply->created_at->diffForHumans();
                }
            }
        }
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
        // return $diff;

        // Parse diff using DiffRenderer
        $renderer = new DiffRenderer($diff);
        $files = $renderer->getFiles();
        return $files;
    }
}
