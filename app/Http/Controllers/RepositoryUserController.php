<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use App\Models\RepositoryUser;
use App\Models\GithubUser;

class RepositoryUserController extends Controller
{
    public static function updateRepositoryUsers()
    {
        // Fetch all repositories
        $repositories = Repository::all();
        $repoCanidates = $repositories;

        foreach ($repositories as $repository) {

            if (! $repository->organization) {
                continue;
            }

            $repoCanidates[] = $repository;
        }

        foreach ($repoCanidates as $repository) {
            // Fetch contributors from GitHub API

            $contributors = githubApi('/repos/'.$repository->full_name.'/contributors');

            // We also need this, to get users who are part of the organization but haven't contributed code
            $org_members = githubApi('/orgs/'.$repository->organization->name.'/members');

            $contributors = array_merge($contributors, $org_members);

            // Always include well-known bot accounts regardless of org membership
            // These are common GitHub bots that should be available for review requests
            $wellKnownBots = [
                (object)[
                    'id' => 175728472,
                    'login' => 'Copilot',
                    'name' => 'Copilot',
                    'avatar_url' => 'https://avatars.githubusercontent.com/in/946600?v=4',
                    'type' => 'Bot'
                ]
            ];

            foreach ($wellKnownBots as $bot) {
                // Only add if not already in contributors list
                $botExists = array_filter($contributors, function ($contributor) use ($bot) {
                    return $contributor->id === $bot->id;
                });
                if (empty($botExists)) {
                    $contributors[] = $bot;
                }
            }

            if (! $contributors) {
                continue;
            }

            foreach ($contributors as $contributor) {
                // Sync GitHub user to database with type information
                GithubUser::updateOrCreate(
                    ['id' => $contributor->id],
                    [
                        'login' => $contributor->login,
                        'name' => $contributor->name ?? $contributor->login,
                        'avatar_url' => $contributor->avatar_url,
                        'type' => $contributor->type ?? 'User',
                        'display_name' => $contributor->name ?? $contributor->login,
                    ]
                );

                // Update or create repository user
                RepositoryUser::updateOrCreate(
                    [
                        'repository_id' => $repository->id,
                        'user_id' => $contributor->id,
                    ],
                    [
                        'name' => $contributor->login,
                        'avatar_url' => $contributor->avatar_url,
                    ]
                );
            }
        }
    }
}
