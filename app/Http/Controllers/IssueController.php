<?php

namespace App\Http\Controllers;

use App\Helpers\ApiHelper;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\Repository;
use App\Models\Issue;
use Carbon\Carbon;

class IssueController extends Controller
{
    public function index($organizationName, $repositoryName, Request $request)
    {
        $organization = Organization::where("name", $organizationName)->first();
        
        $query = Repository::where("name", $repositoryName);
        if ($organization) {
            $query->where("organization_id", $organization->organization_id);
        }

        $repository = $query->firstOrFail();

        return view("repository.issue.issues", [
            "organization" => $organization,
            "repository" => $repository
        ]);
    }

    public static function show($organizationName, $repositoryName, $issueNumber)
    {
        // User repositories have "user" as organization name in the URL, while being null in the DB
        if ($organizationName === "user") {
            $organizationName = null;
        }

        $organization = Organization::where("name", $organizationName)->first();

        $query = Repository::where("name", $repositoryName);
        if ($organization) {
            $query->where("organization_id", $organization->organization_id);
        }

        $repository = $query->firstOrFail();

        $issue = Issue::where("repository_full_name", $repository->full_name)
            ->where("number", $issueNumber)
            ->firstOrFail();

        $timeline = ApiHelper::githubApi("/repos/{$repository->full_name}/issues/{$issueNumber}/timeline");

        return view("repository.issue", [
            "organization" => $organization,
            "repository" => $repository,
            "timeline" => $timeline,
            "issue" => $issue,
        ]);
    }

    public static function getIssues($organizationName, $repositoryName, Request $request)
    {
        $state = $request->query("state", "open");

        $organization = Organization::where("name", $organizationName)->first();

        $query = Repository::where("name", $repositoryName);
        if ($organization) {
            $query->where("organization_id", $organization->organization_id);
        }
        $repository = $query->firstOrFail();

        $issues = $repository->issues()
            ->paginate(30);

        return view("repository.issue.list", [
            "organization" => $organization,
            "repository" => $repository,
            "issues" => $issues,
        ]);
    }
}
