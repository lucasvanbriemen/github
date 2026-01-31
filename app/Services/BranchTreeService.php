<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Commit;
use App\Models\Repository;
use App\Models\Item;
use Illuminate\Support\Facades\Cache;

class BranchTreeService
{
    /**
     * Build a tree structure of branches with parent-child relationships
     * based on Git commit ancestry
     */
    public function buildTree(int $repositoryId): array
    {
        $repository = Repository::findOrFail($repositoryId);

        // Get all branches with their latest commits
        $branches = $repository->branches()
            ->with('commits')
            ->get();

        if ($branches->isEmpty()) {
            return [];
        }

        // Create a map of branch names to branch objects
        $branchMap = $branches->keyBy('name');

        // For each branch, determine its parent
        $branchData = [];
        foreach ($branches as $branch) {
            $parentName = $this->determineBranchParent($branch, $branchMap, $repository);

            $branchData[] = [
                'id' => $branch->id,
                'name' => $branch->name,
                'parent_id' => null,
                'parent_name' => $parentName,
                'is_default' => $branch->name === $repository->default_branch,
                'latest_commit' => $branch->commits->first(),
            ];
        }

        // Now resolve parent_ids
        $branchDataById = [];
        foreach ($branchData as &$data) {
            $branchDataById[$data['id']] = $data;

            if ($data['parent_name']) {
                // Find the parent branch
                foreach ($branchData as $potentialParent) {
                    if ($potentialParent['name'] === $data['parent_name']) {
                        $data['parent_id'] = $potentialParent['id'];
                        break;
                    }
                }
            }

            // If still no parent found and not default, try to find a parent
            if (!$data['parent_id'] && !$data['is_default']) {
                // Default to the main/master branch as parent
                foreach ($branchData as $potentialParent) {
                    if ($potentialParent['is_default']) {
                        $data['parent_id'] = $potentialParent['id'];
                        break;
                    }
                }
            }
        }

        // Enrich with PR data
        $enriched = $this->enrichWithPRData($branchData, $repositoryId);

        return $enriched;
    }

    /**
     * Determine the parent branch for a given branch
     * by checking commit ancestry
     */
    private function determineBranchParent(Branch $branch, $branchMap, Repository $repository): ?string
    {
        // Get the first/oldest commit of this branch
        $commits = $branch->commits()
            ->orderBy('created_at', 'asc')
            ->limit(1)
            ->get();

        if ($commits->isEmpty()) {
            // No commits, default to main/master
            return $this->getDefaultBranchName($repository);
        }

        $firstCommit = $commits->first();

        // Check which other branch contains this commit
        foreach ($branchMap as $otherBranch) {
            if ($otherBranch->id === $branch->id) {
                continue;
            }

            $hasCommit = $otherBranch->commits()
                ->where('sha', $firstCommit->sha)
                ->exists();

            if ($hasCommit) {
                // This branch likely spawned from otherBranch
                return $otherBranch->name;
            }
        }

        // No parent found, default to main/master
        return $this->getDefaultBranchName($repository);
    }

    /**
     * Get the default branch name (main or master)
     */
    private function getDefaultBranchName(Repository $repository): ?string
    {
        return $repository->master_branch ?? 'master';
    }

    /**
     * Enrich branch data with PR information
     */
    private function enrichWithPRData(array $branchData, int $repositoryId): array
    {
        // Get all PRs for this repository
        $prs = Item::where('repository_id', $repositoryId)
            ->where('type', 'pull_request')
            ->with('details')
            ->get();

        // Create a map of head_branch to PR
        $prsByBranch = [];
        foreach ($prs as $pr) {
            $headBranch = $pr->details->head_branch ?? null;
            if ($headBranch) {
                $prsByBranch[$headBranch] = [
                    'number' => $pr->number,
                    'title' => $pr->title,
                    'state' => $pr->state,
                    'pr_id' => $pr->id,
                ];
            }
        }

        // Enrich branch data with PR info
        foreach ($branchData as &$data) {
            $data['pull_request'] = $prsByBranch[$data['name']] ?? null;

            if ($data['latest_commit']) {
                $data['last_commit_sha'] = $data['latest_commit']->sha;
                $data['last_commit_date'] = $data['latest_commit']->created_at?->toIso8601String();
            }

            // Remove temporary fields
            unset($data['parent_name']);
            unset($data['latest_commit']);
        }

        return $branchData;
    }
}
