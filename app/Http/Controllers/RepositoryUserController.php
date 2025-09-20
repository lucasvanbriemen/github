<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Repository;
use App\Models\RepositoryUser;

class RepositoryUserController extends Controller
{
    public static function updateRepositoryUsers()
    {
        // Fetch all repositories
        $repositories = Repository::all();
        $repoCanidates = $repositories;
        
        foreach ($repositories as $repository) {

            if (!$repository->organization) {
                continue;
            }

            $repoCanidates[] = $repository;
        }


        foreach ($repoCanidates as $repository) {
            // Fetch contributors from GitHub API

            $contributors = githubApi("/repos/" . $repository->full_name . "/contributors");

            // We also need this, to get users who are part of the organization but haven't contributed code
            $org_members = githubApi("/orgs/" . $repository->organization->name . "/members");

            $contributors = array_merge($contributors, $org_members);

            if (!$contributors) {
                continue;
            }

            foreach ($contributors as $contributor) {
                // Update or create repository user
                RepositoryUser::updateOrCreate(
                    [
                        "repository_id" => $repository->id,
                        "user_id" => $contributor->id,
                    ],
                    [
                        "name" => $contributor->login,
                        "avatar_url" => $contributor->avatar_url,
                    ]
                );
            }
        }
    }
}
