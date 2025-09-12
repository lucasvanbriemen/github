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

        $page = request()->query("page", 90);
        $apiIssues = ApiHelper::githubApi("/repos/{$repository->full_name}/issues?page={$page}&per_page=100&state=all");

        dd($apiIssues[2]);
    }

    public function updateIssues() {
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
            $apiIssues = ApiHelper::githubApi("/repos/{$repository->full_name}/issues?page=1&per_page=100&state=all&since={$last_update_after}");
            // Process the issues as needed
            // For example, you might want to store them in your database
            foreach ($apiIssues as $issue) {
                if (property_exists($issue, "pull_request")) {
                    // It"s a pull request, skip it
                    continue;
                }
                Issue::updateOrCreate(
                    ["github_id" => $issue->id],
                    [
                        "repository_full_name" => $repository->full_name,
                        "number" => $issue->number,
                        "title" => $issue->title,
                        "body" => $issue->body,
                        "last_updated" => $issue->updated_at,
                        "state" => $issue->state,
                    ]
                );
            }
        }
    }
}