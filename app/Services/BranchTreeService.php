<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Repository;
use App\Models\Item;
use App\GithubConfig;

class BranchTreeService
{
    /**
     * Build a nested tree structure of all branches with parent-child relationships
     * Returns all branches in the repository organized hierarchically
     */
    public function buildTree(int $repositoryId): array
    {
        $repository = Repository::findOrFail($repositoryId);

        // Debug logging
        error_log('[BranchTreeService] Building tree for repo: ' . $repository->full_name);
        error_log('[BranchTreeService] master_branch: ' . ($repository->master_branch ?? 'NULL'));

        // Get all branches with their latest commit in one query
        $branches = $repository->branches()
            ->select('branches.id', 'branches.name', 'branches.created_at')
            ->with([
                'commits' => function ($query) {
                    $query->select('sha', 'branch_id', 'created_at', 'user_id')
                        ->orderBy('created_at', 'desc');
                }
            ])
            ->get();

        if ($branches->isEmpty()) {
            return [];
        }

        error_log('[BranchTreeService] Total branches loaded: ' . $branches->count());

        // Load all PR data upfront
        $prsByBranch = $this->getPRsByBranch($repositoryId);

        // Build branch data with parent relationships
        $branchData = [];
        $branchMap = [];
        $defaultBranchFound = false;

        foreach ($branches as $branch) {
            $isDefault = $branch->name === $repository->master_branch;
            if ($isDefault) {
                $defaultBranchFound = true;
                error_log('[BranchTreeService] Found default branch: ' . $branch->name);
            }
            $branchMap[$branch->name] = [
                'id' => $branch->id,
                'is_default' => $isDefault,
            ];

            $branchData[] = [
                'id' => $branch->id,
                'name' => $branch->name,
                'parent_id' => null,
                'is_default' => $isDefault,
                'commits' => $branch->commits,
                'pull_request' => $prsByBranch[$branch->name] ?? null,
            ];
        }

        // Determine parent relationships based on PR data
        foreach ($branchData as &$data) {
            // Default branch should NEVER have a parent
            if ($data['is_default']) {
                $data['parent_id'] = null;
                continue;
            }

            // If branch has a PR, the base_branch of that PR is the parent
            if ($data['pull_request']) {
                $baseBranch = $data['pull_request']['base_branch'] ?? null;
                if ($baseBranch && isset($branchMap[$baseBranch])) {
                    $data['parent_id'] = $branchMap[$baseBranch]['id'];
                }
            }

            // If still no parent, default to the main/master branch
            if (!$data['parent_id']) {
                foreach ($branchData as $potentialParent) {
                    if ($potentialParent['is_default']) {
                        $data['parent_id'] = $potentialParent['id'];
                        break;
                    }
                }
            }
        }

        // Build nested tree structure with all branches (no filtering)
        $treeStructure = $this->buildNestedTreeStructure($branchData);

        // Debug: log total branches
        error_log('[BranchTreeService] Result - Total branches: ' . count($branchData));

        return $treeStructure;
    }

/**
     * Build nested tree structure with children arrays
     */
    private function buildNestedTreeStructure(array $branchData): array
    {
        // Clean all branch data and add children array
        $cleanedBranches = [];
        $branchesById = [];

        foreach ($branchData as $data) {
            $cleaned = $this->cleanBranchData($data);
            $cleaned['children'] = [];
            $cleanedBranches[] = $cleaned;
            $branchesById[$cleaned['id']] = &$cleanedBranches[count($cleanedBranches) - 1];
        }

        // Build parent-child relationships
        $roots = [];
        foreach ($cleanedBranches as &$branch) {
            if ($branch['parent_id'] && isset($branchesById[$branch['parent_id']])) {
                // Add this branch as a child of its parent
                $branchesById[$branch['parent_id']]['children'][] = &$branch;
            } else {
                // This is a root branch
                $roots[] = &$branch;
            }
        }

        return $roots;
    }

    /**
     * Clean up branch data for response
     */
    private function cleanBranchData(array $data): array
    {
        // Extract last commit info if exists
        $latestCommit = null;
        if (!empty($data['commits'])) {
            $latestCommit = $data['commits']->first();
        }

        $cleaned = [
            'id' => $data['id'],
            'name' => $data['name'],
            'parent_id' => $data['parent_id'],
            'is_default' => $data['is_default'],
            'pull_request' => $data['pull_request'],
        ];

        if ($latestCommit) {
            $cleaned['last_commit_sha'] = $latestCommit->sha;
            $cleaned['last_commit_date'] = $latestCommit->created_at?->toIso8601String();
        }

        return $cleaned;
    }

    /**
     * Get all PRs mapped by their head_branch
     * Load all PR data in a single efficient query
     */
    private function getPRsByBranch(int $repositoryId): array
    {
        $prs = Item::where('repository_id', $repositoryId)
            ->where('type', 'pull_request')
            ->select('id', 'number', 'title', 'state')
            ->with([
                'details' => function ($query) {
                    $query->select('id', 'head_branch', 'base_branch');
                }
            ])
            ->get();

        $prsByBranch = [];
        foreach ($prs as $pr) {
            $headBranch = $pr->details?->head_branch;
            if ($headBranch) {
                $prsByBranch[$headBranch] = [
                    'number' => $pr->number,
                    'title' => $pr->title,
                    'state' => $pr->state,
                    'base_branch' => $pr->details?->base_branch,
                    'pr_id' => $pr->id,
                ];
            }
        }

        return $prsByBranch;
    }
}
