<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Organization;
use App\Models\Repository;

class ItemController extends Controller
{
    public static function show($organizationName, $repositoryName, $issueNumber)
    {
        [$organization, $repository] = self::getRepositoryWithOrganization($organizationName, $repositoryName);

        $item = Item::where('repository_id', $repository->id)
            ->where('number', $issueNumber)
            ->with(['assignees', 'openedBy', 'comments' => function($query) {
                $query->with('author');
            }])
            ->firstOrFail();
        
        $item->body = self::processMarkdownImages($item->body);
        $item->created_at_human = $item->created_at->diffForHumans();

        return response()->json($item);
    }

  
    private static function getRepositoryWithOrganization($organizationName, $repositoryName)
    {
        $organization = Organization::where('name', $organizationName)->first();

        $query = Repository::with('organization')->where('name', $repositoryName)
            ->where('organization_id', $organization->id);
        $repository = $query->firstOrFail();

        return [$organization, $repository];
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
                return "<img{$matches[1]}src=\"{$proxyUrl}\"{$matches[3]}>";
            },
            $content
        );

        return $content;
    }
}
