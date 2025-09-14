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

        // Build assignee options from all issues in repository
        $allAssignees = [];
        $repository->issues()->select('assignees')->chunk(500, function ($rows) use (&$allAssignees) {
            foreach ($rows as $row) {
                if (empty($row->assignees) || !is_array($row->assignees)) continue;
                foreach ($row->assignees as $a) {
                    if (!isset($a['login'])) continue;
                    $allAssignees[$a['login']] = [
                        'login' => $a['login'],
                        'avatar_url' => $a['avatar_url'] ?? null,
                    ];
                }
            }
        });
        ksort($allAssignees);

        return view("repository.issues", [
            "organization" => $organization,
            "repository" => $repository,
            "issues" => $issues,
            "filters" => [
                'state' => $state,
                'assignee' => $assignee,
            ],
            "assignees" => array_values($allAssignees),
        ]);
    }

    public static function updateIssues() {
        $repositories = Repository::all();
        $repoCanidates = [];
        foreach ($repositories as $repository) {
            if ($repository->last_updated > now()->subMinutes(60)) {
                continue;
            }

            $repoCanidates[] = $repository;
        }

        foreach ($repoCanidates as $repository) {
            $last_update_after = now()->subHours(6)->toIso8601String();
            
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
    }
}
