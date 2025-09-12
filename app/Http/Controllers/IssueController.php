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

        dd($apiIssues);
    }

    public function updateIssues() {
        $repositories = Repository::all();
        $repoCanidates = [];
        foreach ($repositories as $repository) {
            if ($repository->last_updated < now()->subMinutes(60)) {
                $repoCanidates[] = $repository;
            }
        }
    }

}