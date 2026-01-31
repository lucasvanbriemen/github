<script>
  import { onMount } from 'svelte';
  import Sidebar from '../sidebar/Sidebar.svelte';
  import SidebarGroup from '../sidebar/group.svelte';
  import BranchTree from './BranchTree.svelte';
  import { organization, repository } from '../stores.js';

  let { params = {} } = $props();

  let branches = $state([]);
  let isLoading = $state(true);
  let error = $state(null);

  // Filter state
  let searchQuery = $state('');
  let showMerged = $state(true);
  let showClosed = $state(false);
  let showDraft = $state(true);
  let showOpen = $state(true);
  let showNoPR = $state(true);

  // Derived filtered branches
  let filteredBranches = $derived.by(() => {
    if (!branches) return [];

    return branches.filter(branch => {
      // Search filter
      if (searchQuery && !branch.name.toLowerCase().includes(searchQuery.toLowerCase())) {
        return false;
      }

      // PR state filters
      if (!branch.pull_request) {
        return showNoPR;
      }

      const prState = branch.pull_request.state;
      if (prState === 'open' && !showOpen) return false;
      if (prState === 'merged' && !showMerged) return false;
      if (prState === 'closed' && !showClosed) return false;
      if (prState === 'draft' && !showDraft) return false;

      return true;
    });
  });

  async function getBranchTree() {
    try {
      isLoading = true;
      error = null;

      const url = route('organizations.repositories.branches.tree', {
        organization: $organization,
        repository: $repository,
      });

      console.log('[BranchTreeView] Fetching branches from URL:', url);
      const json = await window.api.get(url);
      console.log('[BranchTreeView] API response:', json);
      branches = json.branches || [];
      console.log('[BranchTreeView] Branches set to:', branches);
    } catch (err) {
      error = 'Failed to load branch tree. Please try again.';
      console.error('[BranchTreeView] Error loading branch tree:', err);
    } finally {
      isLoading = false;
    }
  }

  function handleNodeClick(branch) {
    if (branch.pull_request) {
      const prUrl = `#/${$organization}/${$repository}/prs/${branch.pull_request.number}`;
      window.location.hash = prUrl;
    }
  }

  onMount(() => {
    getBranchTree();
  });
</script>

<div class="branch-tree-view">
  <Sidebar>
    <SidebarGroup title="Search">
      <input
        type="text"
        placeholder="Search branches..."
        bind:value={searchQuery}
        class="sidebar-input"
      />
    </SidebarGroup>

    <SidebarGroup title="Filters">
      <label class="checkbox-label">
        <input type="checkbox" bind:checked={showOpen} />
        <span>Open PRs</span>
      </label>
      <label class="checkbox-label">
        <input type="checkbox" bind:checked={showDraft} />
        <span>Draft PRs</span>
      </label>
      <label class="checkbox-label">
        <input type="checkbox" bind:checked={showMerged} />
        <span>Merged PRs</span>
      </label>
      <label class="checkbox-label">
        <input type="checkbox" bind:checked={showClosed} />
        <span>Closed PRs</span>
      </label>
      <label class="checkbox-label">
        <input type="checkbox" bind:checked={showNoPR} />
        <span>No PR</span>
      </label>
    </SidebarGroup>

    <SidebarGroup title="Legend">
      <div class="legend">
        <div class="legend-item">
          <div class="legend-dot" style="background-color: #1f6feb;"></div>
          <span>Default</span>
        </div>
        <div class="legend-item">
          <div class="legend-dot" style="background-color: #3fb950;"></div>
          <span>Open</span>
        </div>
        <div class="legend-item">
          <div class="legend-dot" style="background-color: #a371f7;"></div>
          <span>Merged</span>
        </div>
        <div class="legend-item">
          <div class="legend-dot" style="background-color: #da3633;"></div>
          <span>Closed</span>
        </div>
        <div class="legend-item">
          <div class="legend-dot" style="background-color: #6e7681;"></div>
          <span>Draft / No PR</span>
        </div>
      </div>
    </SidebarGroup>
  </Sidebar>

  <div class="branch-tree-content">
    {#if isLoading}
      <div class="loading">
        <div class="spinner"></div>
        <p>Loading branches...</p>
      </div>
    {:else if error}
      <div class="error">
        <p>{error}</p>
        <button onclick={getBranchTree} class="retry-button">Try Again</button>
      </div>
    {:else if branches.length === 0}
      <div class="empty">
        <p>No branches found</p>
      </div>
    {:else}
      <BranchTree branches={branches} onNodeClick={handleNodeClick} />
    {/if}
  </div>
</div>

<style>
  :global(.branch-tree-view) {
    display: flex;
    width: 100%;
    height: 100%;
    gap: 0;
  }

  :global(.branch-tree-content) {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    background-color: #ffffff;
  }

  :global(.sidebar-input) {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d0d7de;
    border-radius: 6px;
    font-size: 12px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
  }

  :global(.sidebar-input:focus) {
    outline: none;
    border-color: #0969da;
    box-shadow: 0 0 0 3px rgba(9, 105, 218, 0.1);
  }

  :global(.checkbox-label) {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 0;
    cursor: pointer;
    font-size: 12px;
    color: #24292f;
  }

  :global(.checkbox-label input) {
    cursor: pointer;
  }

  :global(.checkbox-label:hover) {
    color: #0969da;
  }

  :global(.legend) {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  :global(.legend-item) {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #57606a;
  }

  :global(.legend-dot) {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 1px solid #d0d7de;
  }

  :global(.loading),
  :global(.error),
  :global(.empty) {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    min-height: 400px;
    color: #57606a;
  }

  :global(.spinner) {
    width: 40px;
    height: 40px;
    border: 3px solid #e5e7eb;
    border-top-color: #0969da;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 16px;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }

  :global(.retry-button) {
    margin-top: 12px;
    padding: 8px 16px;
    background-color: #0969da;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
  }

  :global(.retry-button:hover) {
    background-color: #0860ca;
  }

  :global(.error p) {
    color: #da3633;
    margin: 0;
  }
</style>
