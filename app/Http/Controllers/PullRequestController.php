<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\PullRequest;
use App\Models\Repository;

class PullRequestController extends Controller
{
    public function index($organizationName, $repositoryName)
    {
        $organization = Organization::where('name', $organizationName)->first();

        $query = Repository::where('name', $repositoryName);
        if ($organization) {
            $query->where('organization_id', $organization->github_id);
        }

        $repository = $query->firstOrFail();

        $pulls = PullRequest::where('repository_id', $repository->github_id)
            ->orderBy('github_id', 'desc')
            ->paginate(30);

        return view('repository.pr.pulls', [
            'organization' => $organization,
            'repository' => $repository,
            'pulls' => $pulls,
        ]);
    }

    public function show($organizationName, $repositoryName, $number)
    {
        if ($organizationName === 'user') {
            $organizationName = null;
        }

        $organization = Organization::where('name', $organizationName)->first();

        $query = Repository::where('name', $repositoryName);
        if ($organization) {
            $query->where('organization_id', $organization->github_id);
        }
        $repository = $query->firstOrFail();

        $pull = PullRequest::where('repository_id', $repository->github_id)
            ->where('number', $number)
            ->firstOrFail();

        return view('repository.pr.pull', [
            'organization' => $organization,
            'repository' => $repository,
            'pull' => $pull,
        ]);
    }
}

