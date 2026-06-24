<?php

namespace App\Http\Controllers;

use App\GithubConfig;
use App\Helpers\ApiHelper;
use App\Models\Item;
use App\Models\Label;
use App\Models\Repository;
use App\Services\RepositoryService;

class RepositoryController extends Controller
{
    public function getBranchesForPRNotices($organizationName, $repositoryName)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $branches = $repository->branches()
            ->showNotice()
            ->whereHas('commits') // only branches that have at least one commit
            ->with(['commits' => function ($q) {
                $q->orderBy('created_at', 'desc')->limit(1);
            }])
            ->get()
            ->map(function ($branch) {
                $branch->last_commit = $branch->commits->first();
                unset($branch->commits);

                return $branch;
            });

        return response()->json($branches);
    }

    public static function updateLabels()
    {
        $repositories = Repository::all();

        $perPage = 100;

        foreach ($repositories as $repository) {
            $page = 1;

            do {
                $labels = ApiHelper::githubApi('/repos/'.$repository->full_name.'/labels?per_page='.$perPage.'&page='.$page);

                if (empty($labels)) {
                    break;
                }

                foreach ($labels as $label) {
                    // Match on (repository_id, name) to align with the table's
                    // unique constraint: GitHub guarantees label names are unique
                    // per repo, while github_id can change if a label is deleted
                    // and recreated under the same name.
                    Label::updateOrCreate(
                        [
                            'repository_id' => $repository->id,
                            'name' => $label->name,
                        ],
                        [
                            'github_id' => $label->id,
                            'color' => $label->color,
                            'description' => $label->description,
                        ]
                    );
                }

                $page++;
            } while (count($labels) === $perPage);
        }
    }

    /**
     * Rebuild the item_labels pivot from GitHub. The issues endpoint returns
     * both issues and pull requests with their labels inline, so we page through
     * it per repository and sync each matching item's labels.
     */
    public static function resyncItemLabels()
    {
        $perPage = 100;

        foreach (Repository::all() as $repository) {
            // Map item number -> item id for fast lookup (covers issues and PRs).
            $itemsByNumber = Item::where('repository_id', $repository->id)->pluck('id', 'number');

            if ($itemsByNumber->isEmpty()) {
                continue;
            }

            $page = 1;

            do {
                $issues = ApiHelper::githubApi(
                    '/repos/'.$repository->full_name.'/issues?state=all&per_page='.$perPage.'&page='.$page
                );

                if (empty($issues)) {
                    break;
                }

                foreach ($issues as $issue) {
                    if (! isset($itemsByNumber[$issue->number])) {
                        continue;
                    }

                    $item = Item::find($itemsByNumber[$issue->number]);
                    if (! $item) {
                        continue;
                    }

                    $labelIds = Label::syncFromGithub($repository->id, $issue->labels ?? []);
                    $item->labels()->sync($labelIds);
                }

                $page++;
            } while (count($issues) === $perPage);
        }
    }

    public function metadata($organizationName, $repositoryName)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $branchNames = $repository->branches()->pluck('name');
        $master_branch = $repository->master_branch;
        $default_assignee = GithubConfig::USERNAME;
        $labels = $repository->labels()->get();
        $milestones = $repository->milestones()->get();

        $assignees = $repository->contributors()->with('githubUser')->get()->map(function ($contributor) {
            return $contributor->githubUser;
        })->values();

        $templatesJson = file_get_contents(resource_path('repository_templates/templates.json'));
        $templates = json_decode($templatesJson, true);

        return response()->json([
            'branches' => $branchNames,
            'assignees' => $assignees,
            'default_assignee' => $default_assignee,
            'master_branch' => $master_branch,
            'templates' => $templates,
            'labels' => $labels,
            'milestones' => $milestones,
        ]);
    }
}
