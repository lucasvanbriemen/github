<script>
  import { onMount } from 'svelte';

  let { branches = [], onNodeClick = () => {} } = $props();

  let branchesByDepth = $state([]);
  let branchesMap = $state(new Map());
  let expandedBranches = $state(new Set());
  let renderError = $state(null);

  const MAX_BRANCHES = 500; // Hard limit to prevent crashes

  const CARD_WIDTH = 180;
  const CARD_HEIGHT = 75;
  const LANE_HEIGHT = 180;
  const CARD_GAP = 16;

  function getCardColor(branch) {
    if (branch.is_default) return '#1f6feb'; // Blue
    if (!branch.pull_request) return '#6e7681'; // Gray
    const state = branch.pull_request.state;
    if (state === 'open') return '#3fb950'; // Green
    if (state === 'merged') return '#a371f7'; // Purple
    if (state === 'draft') return '#6e7681'; // Gray
    if (state === 'closed') return '#da3633'; // Red
    return '#6e7681';
  }

  function getStateLabel(branch) {
    if (branch.is_default) return '●';
    if (!branch.pull_request) return '○';
    const state = branch.pull_request.state;
    if (state === 'open') return '● OPEN';
    if (state === 'merged') return '✓ MERGED';
    if (state === 'draft') return '◐ DRAFT';
    if (state === 'closed') return '✕ CLOSED';
    return '●';
  }

  function calculateBranchDepth(branch, visited = new Set(), maxDepth = 100) {
    // Prevent infinite recursion with circular references
    if (visited.has(branch.id) || visited.size > maxDepth) {
      console.warn('[BranchTree] Circular reference detected or max depth exceeded for branch:', branch.name);
      return 0;
    }

    if (!branch.parent_id) return 0;

    visited.add(branch.id);
    const parent = branchesMap.get(branch.parent_id);
    if (!parent) return 1;

    return calculateBranchDepth(parent, visited, maxDepth) + 1;
  }

  function organizeBranchesByDepth() {
    if (!branches || branches.length === 0) {
      branchesByDepth = [];
      renderError = null;
      return;
    }

    renderError = null;

    try {
      console.log('[BranchTree] Processing', branches.length, 'branches');

      // Cap branches to prevent crashes
      let branchesToProcess = branches;
      if (branches.length > MAX_BRANCHES) {
        console.warn('[BranchTree] Capping branches from', branches.length, 'to', MAX_BRANCHES);
        branchesToProcess = branches.slice(0, MAX_BRANCHES);
      }

      // Create map of branches by ID with safety checks
      branchesMap = new Map();
      const validBranches = [];

      for (let i = 0; i < branchesToProcess.length; i++) {
        const b = branchesToProcess[i];
        if (b && typeof b === 'object' && b.id) {
          branchesMap.set(b.id, b);
          validBranches.push(b);
        }
      }

      console.log('[BranchTree] Valid branches:', validBranches.length);

      if (validBranches.length === 0) {
        branchesByDepth = [];
        return;
      }

      // Calculate depth for each branch with error handling
      const branchesWithDepth = [];
      for (let i = 0; i < validBranches.length; i++) {
        const b = validBranches[i];
        try {
          branchesWithDepth.push({
            ...b,
            depth: calculateBranchDepth(b)
          });
        } catch (err) {
          console.warn('[BranchTree] Error calculating depth for branch:', b.name, err);
          branchesWithDepth.push({ ...b, depth: 0 });
        }
      }

      // Group by depth
      const maxDepth = Math.max(...branchesWithDepth.map(b => b.depth), 0);

      if (maxDepth > 100) {
        console.warn('[BranchTree] Unusual depth detected:', maxDepth);
      }

      const grouped = Array.from({ length: Math.min(maxDepth + 1, 100) }, () => []);

      for (let i = 0; i < branchesWithDepth.length; i++) {
        const branch = branchesWithDepth[i];
        const depth = Math.min(branch.depth, grouped.length - 1);
        grouped[depth].push(branch);
      }

      // Sort each group by name
      for (let i = 0; i < grouped.length; i++) {
        const group = grouped[i];
        try {
          group.sort((a, b) => {
            const nameA = (a && a.name) || '';
            const nameB = (b && b.name) || '';
            return nameA.localeCompare(nameB);
          });
        } catch (err) {
          console.warn('[BranchTree] Error sorting group', i, ':', err);
        }
      }

      branchesByDepth = grouped;
      console.log('[BranchTree] Organized branches by depth:', grouped.map(g => g.length).join(', '));
    } catch (err) {
      console.error('[BranchTree] Fatal error organizing branches:', err);
      renderError = 'Failed to organize branches. Please refresh the page.';
      branchesByDepth = [];
    }
  }

  function toggleExpand(branchId) {
    if (expandedBranches.has(branchId)) {
      expandedBranches.delete(branchId);
    } else {
      expandedBranches.add(branchId);
    }
    expandedBranches = expandedBranches;
  }

  function getChildBranches(parentId) {
    try {
      return branches
        .filter(b => b && typeof b === 'object' && b.parent_id === parentId)
        .slice(0, 100); // Limit to prevent rendering too many children
    } catch (err) {
      console.warn('[BranchTree] Error getting child branches:', err);
      return [];
    }
  }

  function handleCardClick(branch) {
    try {
      if (branch && branch.pull_request) {
        onNodeClick(branch);
      }
    } catch (err) {
      console.error('[BranchTree] Error handling card click:', err);
    }
  }

  onMount(() => {
    try {
      console.log('[BranchTree] Component mounted with', branches?.length, 'branches');
      organizeBranchesByDepth();
    } catch (err) {
      console.error('[BranchTree] Error in onMount:', err);
    }
  });

  $effect(() => {
    try {
      if (branches && branches.length > 0) {
        console.log('[BranchTree] Branches changed, reorganizing');
        organizeBranchesByDepth();
      }
    } catch (err) {
      console.error('[BranchTree] Error in effect:', err);
    }
  });
</script>

<div class="swimlane-container">
  {#if renderError}
    <div class="render-error">
      <p>⚠️ {renderError}</p>
      <p style="font-size: 12px; color: #57606a;">
        Showing branches capped at {MAX_BRANCHES} to prevent performance issues.
        Try using search or filters to narrow down results.
      </p>
    </div>
  {/if}

  {#if branchesByDepth.length === 0 && !renderError}
    <div class="empty-state">
      <p>No branches to display</p>
    </div>
  {/if}

  {#each branchesByDepth as lane, depthIndex (depthIndex)}
    <div class="swimlane" style="--depth: {depthIndex}">
      <div class="swimlane-header">
        <span class="depth-label">Level {depthIndex}</span>
        <span class="count-badge">{lane.length} branch{lane.length !== 1 ? 'es' : ''}</span>
      </div>

      <div class="swimlane-content">
        {#each lane as branch (branch.id)}
          <div class="branch-card-wrapper">
            <div
              class="branch-card"
              style="border-color: {getCardColor(branch)}"
              on:click={() => handleCardClick(branch)}
              role="button"
              tabindex="0"
            >
              <div class="card-header">
                <span class="branch-name" title={branch.name}>
                  {branch.name.length > 20 ? branch.name.substring(0, 17) + '...' : branch.name}
                </span>
                {#if getChildBranches(branch.id).length > 0}
                  <button
                    class="expand-btn"
                    on:click={(e) => {
                      e.stopPropagation();
                      toggleExpand(branch.id);
                    }}
                    title={expandedBranches.has(branch.id) ? 'Collapse' : 'Expand'}
                  >
                    {expandedBranches.has(branch.id) ? '▼' : '▶'}
                  </button>
                {/if}
              </div>

              <div class="card-info">
                {#if branch.is_default}
                  <span class="info-label">(default)</span>
                {:else if !branch.pull_request}
                  <span class="info-label">(no PR)</span>
                {:else}
                  <span class="info-label">#{branch.pull_request.number}</span>
                {/if}
              </div>

              <div class="card-state" style="color: {getCardColor(branch)}">
                {getStateLabel(branch)}
              </div>
            </div>

            {#if expandedBranches.has(branch.id) && getChildBranches(branch.id).length > 0}
              <div class="children-preview">
                {#each getChildBranches(branch.id) as child (child.id)}
                  <div class="child-item">
                    <span class="child-name">{child.name}</span>
                    <span class="child-state" style="color: {getCardColor(child)}">
                      {getStateLabel(child)}
                    </span>
                  </div>
                {/each}
              </div>
            {/if}
          </div>
        {/each}
      </div>
    </div>
  {/each}
</div>

<style>
  :global(.swimlane-container) {
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: #f6f8fa;
    display: flex;
    flex-direction: column;
    gap: 0;
  }

  :global(.render-error) {
    padding: 16px 20px;
    background-color: #fff8c5;
    border-left: 4px solid #d29922;
    color: #6f42c1;
  }

  :global(.render-error p) {
    margin: 8px 0;
  }

  :global(.render-error p:first-child) {
    margin-top: 0;
  }

  :global(.empty-state) {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 1;
    color: #57606a;
    font-size: 14px;
  }

  :global(.swimlane) {
    min-height: 220px;
    border-bottom: 2px solid #e1e4e8;
    background-color: #ffffff;
    display: flex;
    flex-direction: column;
  }

  :global(.swimlane-header) {
    padding: 16px 20px;
    background-color: #f6f8fa;
    border-bottom: 1px solid #e1e4e8;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    font-size: 13px;
    color: #24292f;
  }

  :global(.depth-label) {
    display: flex;
    align-items: center;
  }

  :global(.count-badge) {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 20px;
    padding: 0 6px;
    background-color: #eaeef2;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 500;
    color: #57606a;
  }

  :global(.swimlane-content) {
    flex: 1;
    padding: 16px 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-content: flex-start;
  }

  :global(.branch-card-wrapper) {
    display: flex;
    flex-direction: column;
  }

  :global(.branch-card) {
    width: 180px;
    padding: 12px;
    background-color: #ffffff;
    border: 2px solid #d1d5da;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  :global(.branch-card:hover) {
    filter: brightness(0.98);
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
  }

  :global(.card-header) {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 4px;
  }

  :global(.branch-name) {
    flex: 1;
    font-size: 12px;
    font-weight: 600;
    color: #24292f;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  :global(.expand-btn) {
    padding: 2px 4px;
    background: none;
    border: none;
    color: #57606a;
    cursor: pointer;
    font-size: 10px;
    flex-shrink: 0;
  }

  :global(.expand-btn:hover) {
    color: #24292f;
  }

  :global(.card-info) {
    font-size: 9px;
    color: #57606a;
    text-align: center;
  }

  :global(.info-label) {
    display: inline-block;
    padding: 2px 6px;
    background-color: #f6f8fa;
    border-radius: 3px;
    font-family: monospace;
  }

  :global(.card-state) {
    font-size: 10px;
    font-weight: 600;
    text-align: center;
    font-family: monospace;
  }

  :global(.children-preview) {
    margin-top: 8px;
    padding: 8px;
    background-color: #f6f8fa;
    border-radius: 6px;
    border-left: 3px solid #a371f7;
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  :global(.child-item) {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 9px;
    padding: 4px 0;
    border-bottom: 1px solid #e1e4e8;
  }

  :global(.child-item:last-child) {
    border-bottom: none;
  }

  :global(.child-name) {
    color: #57606a;
    font-weight: 500;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  :global(.child-state) {
    flex-shrink: 0;
    margin-left: 4px;
    font-weight: 600;
  }
</style>
