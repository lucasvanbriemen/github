<?php

namespace App\Http\Controllers;

use App\Helpers\ApiHelper;
use App\Models\Organization;
use App\Models\Repository;
use Highlight\Highlighter;
use Illuminate\Http\Request;

class RepositoryController extends Controller
{
    public function show(Request $request, $organizationName, $repositoryName, $filePath = null)
    {
        // User repositories have "user" as organization name in the URL, while being null in the DB
        if ($organizationName === 'user') {
            $organizationName = null;
        }

        $organization = Organization::where('name', $organizationName)->first();

        $query = Repository::where('name', $repositoryName);
        if ($organization) {
            $query->where('organization_id', $organization->id);
        }
        $repository = $query->firstOrFail();

        return view('repository.show', compact('organization', 'repository'));
    }

    public function show_file_tree(Request $request, $organizationName, $repositoryName, $filePath = null)
    {
        // User repositories have "user" as organization name in the URL, while being null in the DB
        if ($organizationName === 'user') {
            $organizationName = null;
        }

        $organization = Organization::where('name', $organizationName)->first();

        $query = Repository::where('name', $repositoryName);
        if ($organization) {
            $query->where('organization_id', $organization->id);
        }
        $repository = $query->firstOrFail();

        $isFile = $request->query('isFile', false);
        $filecontent = ApiHelper::githubApi("/repos/{$repository->full_name}/contents/".($filePath ?? ''));
        if ($isFile) {
            $filecontent = file_get_contents($filecontent->download_url);
            $hl = new Highlighter;
            $filecontent = $hl->highlightAuto($filecontent)->value;
        } else {
            $filecontent = self::sortApiContent($filecontent);
        }

        return view('repository.file_display', compact('organization', 'repository', 'filecontent', 'isFile'));
    }

    private static function sortApiContent($apiObject)
    {
        // Sort directories first, then files, both alphabetically
        $sortedFolders = [];
        $sortedFiles = [];
        foreach ($apiObject as $item) {
            if ($item->type === 'dir') {
                $sortedFolders[] = $item;
            } elseif ($item->type === 'file') {
                $sortedFiles[] = $item;
            }
        }

        // Sort alphabetically within each type
        usort($sortedFolders, function ($a, $b) {
            return strcasecmp($a->name, $b->name);
        });

        // Sort alphabetically within each type
        usort($sortedFiles, function ($a, $b) {
            return strcasecmp($a->name, $b->name);
        });

        return array_merge($sortedFolders, $sortedFiles);
    }
}
