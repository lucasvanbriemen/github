<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Repository;
use App\Models\Item;

class BranchTreeService
{
    /**
     * Build a tree structure of branches with parent-child relationships
     * Optimized to avoid N+1 queries and timeout issues
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
                    $query->select('sha', 'branch_id', 'created_at')
                        ->orderBy('created_at', 'desc')
                        ->limit(1);
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
                'latest_commit' => $branch->commits->first(),
                'pull_request' => $prsByBranch[$branch->name] ?? null,
            ];
        }

        // Determine parent relationships based on PR data first (most reliable)
        foreach ($branchData as &$data) {
            // If branch has a PR, the base_branch of that PR is the parent
            if ($data['pull_request']) {
                $baseBranch = $data['pull_request']['base_branch'] ?? null;
                if ($baseBranch && isset($branchMap[$baseBranch])) {
                    $data['parent_id'] = $branchMap[$baseBranch]['id'];
                }
            }

            // If still no parent and not default, default to master/main
            if (!$data['parent_id'] && !$data['is_default']) {
                // Find the default branch and use it as parent
                foreach ($branchData as $potentialParent) {
                    if ($potentialParent['is_default']) {
                        $data['parent_id'] = $potentialParent['id'];
                        break;
                    }
                }
            }
        }

        // Clean up temporary data
        foreach ($branchData as &$data) {
            if ($data['latest_commit']) {
                $data['last_commit_sha'] = $data['latest_commit']->sha;
                $data['last_commit_date'] = $data['latest_commit']->created_at?->toIso8601String();
            }
            unset($data['latest_commit']);
        }

        // Debug: count default branches in result
        $defaultCount = count(array_filter($branchData, fn($b) => $b['is_default']));
        $nullParentCount = count(array_filter($branchData, fn($b) => $b['parent_id'] === null));
        error_log('[BranchTreeService] Result stats - Default branches: ' . $defaultCount . ', Null parent: ' . $nullParentCount);

        return $branchData;
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
