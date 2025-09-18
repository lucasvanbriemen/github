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

        return view("repository.issue", [
            "organization" => $organization,
            "repository" => $repository,
            "issue" => $issue,
        ]);
    }

    public static function updateIssues() {
        $repositories = Repository::all();
        
        $count = 0;
        foreach ($repositories as $repository) {
            $last_update_after = now()->subHours(168)->toIso8601String();
            $last_update_after = urlencode($last_update_after);

            // Github stops at page 100
            $max_page = 99;


            for ($page = 1; $page <= $max_page; $page++) {
                $apiIssues = ApiHelper::githubApi("/repos/{$repository->full_name}/issues?page={$page}&per_page=100&state=all&since={$last_update_after}");
                if (empty($apiIssues)) {
                    break;
                }
                foreach ($apiIssues as $issue) {
                    if (property_exists($issue, "pull_request")) {
                        // It's a pull request, skip it
                        continue;
                    }

                    $count++;

                    Issue::updateOrCreate(
                        ["github_id" => $issue->id],
                        [
                            "repository_full_name" => $repository->full_name,
                            "number" => $issue->number,
                            "title" => $issue->title,
                            "body" => $issue->body,
                            "last_updated" => Carbon::parse($issue->updated_at)->format('Y-m-d H:i:s'),
                            "state" => $issue->state,
                            "opened_by" => $issue->user->login,
                            "opened_by_image" => $issue->user->avatar_url,
                            "labels" => json_encode($issue->labels),
                            "assignees" => json_encode($issue->assignees),
                        ]
                    );
                }
            }

        }
        dd("Updated {$count} issues");
    }
}
