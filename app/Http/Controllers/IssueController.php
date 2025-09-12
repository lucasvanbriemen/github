<?php

namespace App\Http\Controllers;

use App\Helpers\ApiHelper;
use App\Models\Organization;
use App\Models\Repository;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    public function index($organizationName, $repositoryName)
    {
        // User repositories have "user" as organization name in the URL, while being null in the DB
        if ($organizationName === "user") {
            $organizationName = null;
        }

        $organization = Organization::where("name", $organizationName)->first();
        
        $query = Repository::where("name", $repositoryName);
        if ($organization) {
            $query->where("organization_id", $organization->id);
        }
        $repository = $query->firstOrFail();

        $page = request()->query("page", 1);
        $apiIssues = ApiHelper::githubApi("/repos/{$repository->full_name}/issues?page={$page}&per_page=60");

        $issues = [];
        foreach ($apiIssues as $issue) {
            if (property_exists($issue, "pull_request")) {
                // It's a pull request, skip it
                continue;
            }
            $issues[] = $issue;
        }

        return view("repository.issues", compact("organization", "repository", "issues", "apiIssues"));
    }

    public function show($organizationName, $repositoryName, $issueNumber)
    {
        // User repositories have "user" as organization name in the URL, while being null in the DB
        if ($organizationName === "user") {
            $organizationName = null;
        }

        $organization = Organization::where("name", $organizationName)->first();
        
        $query = Repository::where("name", $repositoryName);
        if ($organization) {
            $query->where("organization_id", $organization->id);
        }
        $repository = $query->firstOrFail();

        $issue = ApiHelper::githubApi("/repos/{$repository->full_name}/issues/{$issueNumber}");

        return view("repository.issue", compact("organization", "repository", "issue"));
    }
}