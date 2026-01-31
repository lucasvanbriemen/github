<script>
  import { onMount } from 'svelte';

  let { branches = [], onNodeClick = () => {} } = $props();

  let searchQuery = $state('');
  let selectedBranchId = $state(null);
  let branchesMap = $state(new Map());

  function getCardColor(branch) {
    if (branch.is_default) return '#1f6feb';
    if (!branch.pull_request) return '#6e7681';
    const state = branch.pull_request.state;
    if (state === 'open') return '#3fb950';
    if (state === 'merged') return '#a371f7';
    if (state === 'draft') return '#6e7681';
    if (state === 'closed') return '#da3633';
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

  // Get filtered search results
  function getSearchResults() {
    if (!searchQuery.trim()) return [];
    const query = searchQuery.toLowerCase();
    return branches
      .filter(b => b && b.name && b.name.toLowerCase().includes(query))
      .slice(0, 20); // Limit to 20 results
  }

  // Get ancestors of a branch (path up to root)
  function getAncestors(branchId) {
    const ancestors = [];
    let current = branchesMap.get(branchId);

    while (current && !current.is_default) {
      ancestors.unshift(current);
      if (!current.parent_id) break;
      current = branchesMap.get(current.parent_id);
    }

    // Add the root/default branch at the end
    const root = Array.from(branchesMap.values()).find(b => b.is_default);
    if (root) {
      ancestors.push(root);
    }

    return ancestors;
  }

  // Get all descendants of a branch
  function getDescendants(branchId, visited = new Set()) {
    if (visited.has(branchId)) return [];
    visited.add(branchId);

    const children = branches.filter(b => b && b.parent_id === branchId);
    const allDescendants = [...children];

    for (const child of children) {
      allDescendants.push(...getDescendants(child.id, visited));
    }

    return allDescendants;
  }

  // Get the branch hierarchy for selected branch
  function getSelectedBranchHierarchy() {
    if (!selectedBranchId) return null;

    const selected = branchesMap.get(selectedBranchId);
    if (!selected) return null;

    return {
      ancestors: getAncestors(selectedBranchId),
      selected: selected,
      descendants: getDescendants(selectedBranchId)
    };
  }

  function handleSelectBranch(branchId) {
    selectedBranchId = branchId;
    searchQuery = '';
  }

  function handleCardClick(branch) {
    if (branch && branch.pull_request) {
      onNodeClick(branch);
    }
  }

  onMount(() => {
    try {
      if (branches && Array.isArray(branches)) {
        branchesMap = new Map(branches.map(b => [b.id, b]));
        console.log('[BranchTree] Initialized with', branches.length, 'branches');
      }
    } catch (err) {
      console.error('[BranchTree] Error initializing:', err);
    }
  });

  $effect(() => {
    if (branches && Array.isArray(branches)) {
      branchesMap = new Map(branches.map(b => [b.id, b]));
    }
  });

  const searchResults = $derived(getSearchResults());
  const hierarchy = $derived(getSelectedBranchHierarchy());
</script>

<div class="branch-view">
  <!-- Search Section -->
  <div class="search-section">
    <input
      type="text"
      placeholder="Search branches..."
      bind:value={searchQuery}
      class="search-input"
    />
    {#if searchQuery && searchResults.length === 0}
      <div class="no-results">No branches found</div>
    {/if}
  </div>

  <!-- Search Results List -->
  {#if searchQuery && searchResults.length > 0}
    <div class="results-list">
      {#each searchResults as branch (branch.id)}
        <button
          class="result-item"
          on:click={() => handleSelectBranch(branch.id)}
          style="border-left-color: {getCardColor(branch)}"
        >
          <span class="result-name">{branch.name}</span>
          <span class="result-state" style="color: {getCardColor(branch)}">
            {getStateLabel(branch)}
          </span>
        </button>
      {/each}
    </div>
  {/if}

  <!-- Branch Hierarchy View -->
  {#if hierarchy}
    <div class="hierarchy-view">
      <!-- Ancestors (path to root) -->
      <div class="path-section">
        <div class="path-label">Path to Root</div>
        {#each hierarchy.ancestors as ancestor, idx (ancestor.id)}
          <div class="path-item" style="--level: {idx}">
            <div class="path-connector"></div>
            <div
              class="path-card"
              style="border-color: {getCardColor(ancestor)}"
            >
              <div class="card-name">{ancestor.name}</div>
              <div class="card-state" style="color: {getCardColor(ancestor)}">
                {getStateLabel(ancestor)}
              </div>
            </div>
          </div>
        {/each}
      </div>

      <!-- Selected Branch -->
      <div class="selected-section">
        <div class="selected-card" style="border-color: {getCardColor(hierarchy.selected)}">
          <div class="selected-name">{hierarchy.selected.name}</div>
          <div class="selected-state" style="color: {getCardColor(hierarchy.selected)}">
            {getStateLabel(hierarchy.selected)}
          </div>
          <div class="pr-info">
            {#if hierarchy.selected.is_default}
              <span>(default branch)</span>
            {:else if !hierarchy.selected.pull_request}
              <span>(no PR)</span>
            {:else}
              <span>#{hierarchy.selected.pull_request.number}</span>
            {/if}
          </div>
          {#if hierarchy.selected.pull_request && hierarchy.selected.pull_request.state !== 'closed'}
            <button
              class="view-pr-btn"
              on:click={() => handleCardClick(hierarchy.selected)}
            >
              View PR
            </button>
          {/if}
        </div>
      </div>

      <!-- Descendants (children) -->
      {#if hierarchy.descendants.length > 0}
        <div class="children-section">
          <div class="children-label">
            Child Branches ({hierarchy.descendants.length})
          </div>
          <div class="children-list">
            {#each hierarchy.descendants as child (child.id)}
              <div
                class="child-card"
                style="border-left-color: {getCardColor(child)}"
                on:click={() => handleSelectBranch(child.id)}
                role="button"
                tabindex="0"
              >
                <div class="child-name">{child.name}</div>
                <div class="child-state" style="color: {getCardColor(child)}">
                  {getStateLabel(child)}
                </div>
              </div>
            {/each}
          </div>
        </div>
      {/if}

      <button class="close-btn" on:click={() => (selectedBranchId = null)}>
        ← Back to Search
      </button>
    </div>
  {/if}

  <!-- Empty State -->
  {#if !searchQuery && !hierarchy}
    <div class="empty-state">
      <p>Search for a branch to get started</p>
      <p style="font-size: 12px; color: #57606a;">
        Use the search box above to find a branch and view its hierarchy
      </p>
    </div>
  {/if}
</div>

<style>
  :global(.branch-view) {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    background-color: #ffffff;
    overflow: hidden;
  }

  :global(.search-section) {
    padding: 16px 20px;
    border-bottom: 1px solid #e1e4e8;
    background-color: #f6f8fa;
    flex-shrink: 0;
  }

  :global(.search-input) {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d0d7de;
    border-radius: 6px;
    font-size: 14px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial,
      sans-serif;
  }

  :global(.search-input:focus) {
    outline: none;
    border-color: #0969da;
    box-shadow: 0 0 0 3px rgba(9, 105, 218, 0.1);
  }

  :global(.no-results) {
    margin-top: 12px;
    padding: 12px;
    text-align: center;
    font-size: 13px;
    color: #57606a;
  }

  :global(.results-list) {
    flex: 1;
    overflow-y: auto;
    border-bottom: 1px solid #e1e4e8;
  }

  :global(.result-item) {
    width: 100%;
    padding: 12px 20px;
    border: none;
    border-left: 3px solid #d1d5da;
    background: none;
    text-align: left;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    transition: background-color 0.2s;
  }

  :global(.result-item:hover) {
    background-color: #f6f8fa;
  }

  :global(.result-name) {
    flex: 1;
    color: #24292f;
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  :global(.result-state) {
    flex-shrink: 0;
    margin-left: 8px;
    font-weight: 600;
    font-size: 11px;
  }

  :global(.hierarchy-view) {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 24px;
  }

  :global(.path-section) {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  :global(.path-label) {
    font-size: 12px;
    font-weight: 600;
    color: #57606a;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  :global(.path-item) {
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    margin-left: calc(var(--level) * 16px);
  }

  :global(.path-connector) {
    width: 2px;
    height: 20px;
    background-color: #d1d5da;
    margin-left: 4px;
  }

  :global(.path-item:first-child .path-connector) {
    display: none;
  }

  :global(.path-card) {
    flex: 1;
    padding: 8px 12px;
    border: 2px solid #d1d5da;
    border-radius: 6px;
    font-size: 12px;
    background-color: #f6f8fa;
  }

  :global(.card-name) {
    font-weight: 600;
    color: #24292f;
  }

  :global(.card-state) {
    font-size: 10px;
    font-weight: 600;
  }

  :global(.selected-section) {
    display: flex;
    justify-content: center;
  }

  :global(.selected-card) {
    width: 220px;
    padding: 16px;
    border: 3px solid #0969da;
    border-radius: 8px;
    background-color: #ffffff;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
    text-align: center;
  }

  :global(.selected-name) {
    font-size: 14px;
    font-weight: 700;
    color: #24292f;
    margin-bottom: 8px;
  }

  :global(.selected-state) {
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 8px;
  }

  :global(.pr-info) {
    font-size: 11px;
    color: #57606a;
    margin-bottom: 12px;
  }

  :global(.view-pr-btn) {
    width: 100%;
    padding: 6px 12px;
    background-color: #0969da;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
  }

  :global(.view-pr-btn:hover) {
    background-color: #0860ca;
  }

  :global(.children-section) {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  :global(.children-label) {
    font-size: 12px;
    font-weight: 600;
    color: #57606a;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  :global(.children-list) {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
  }

  :global(.child-card) {
    padding: 12px;
    border-left: 3px solid #d1d5da;
    border-radius: 6px;
    background-color: #f6f8fa;
    cursor: pointer;
    transition: all 0.2s;
  }

  :global(.child-card:hover) {
    background-color: #ffffff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  }

  :global(.child-name) {
    font-size: 12px;
    font-weight: 600;
    color: #24292f;
    margin-bottom: 6px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  :global(.child-state) {
    font-size: 10px;
    font-weight: 600;
  }

  :global(.close-btn) {
    align-self: flex-start;
    padding: 8px 12px;
    background-color: #f6f8fa;
    border: 1px solid #d0d7de;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    transition: background-color 0.2s;
  }

  :global(.close-btn:hover) {
    background-color: #e5e7eb;
  }

  :global(.empty-state) {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #57606a;
  }

  :global(.empty-state p) {
    margin: 8px 0;
  }

  :global(.empty-state p:first-child) {
    margin-top: 0;
    font-size: 14px;
    font-weight: 500;
  }
</style>
