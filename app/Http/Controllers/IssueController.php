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

        // Filters
        $state = $request->query('state', 'open'); // open | closed | all
        $assignee = $request->query('assignee');   // username | unassigned | null

        $stateParam = $state === 'all' ? null : $state;
        $issues = $repository->issues($stateParam, $assignee)
            ->paginate(30)
            ->appends($request->query());

        $assignees = $repository->users;

        return view("repository.issues", [
            "organization" => $organization,
            "repository" => $repository,
            "issues" => $issues,
            "filters" => [
                'state' => $state,
                'assignee' => $assignee,
            ],
            "assignees" => $assignees,
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
            $query->where("organization_id", $organization->id);
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
}
