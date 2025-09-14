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
        $repoCanidates = [];
        
        foreach ($repositories as $repository) {
            $repoCanidates[] = $repository;
        }


        foreach ($repoCanidates as $repository) {
                // Fetch contributors from GitHub API
            $contributors = githubApi("/repos/" . $repository->full_name . "/contributors");

            foreach ($contributors as $contributor) {
                // Update or create repository user
                RepositoryUser::updateOrCreate(
                    [
                        'repository_id' => $repository->id,
                        'user_id' => $contributor['id'],
                    ],
                    [
                        'name' => $contributor['login'],
                        'avatar_url' => $contributor['avatar_url'],
                    ]
                );
            }

        }
        
    }
}
