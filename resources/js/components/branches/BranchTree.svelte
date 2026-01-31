<script>
  import { onMount } from 'svelte';

  let { branches = [], onNodeClick = () => {} } = $props();

  let searchQuery = $state('');
  let selectedBranchId = $state(null);
  let branchesMap = $state(new Map());
  let svgElement = $state(null);

  const CARD_WIDTH = 160;
  const CARD_HEIGHT = 70;
  const VERTICAL_SPACING = 120;
  const HORIZONTAL_SPACING = 200;

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

  function getSearchResults() {
    if (!searchQuery.trim()) return [];
    const query = searchQuery.toLowerCase();
    return branches
      .filter(b => b && b.name && b.name.toLowerCase().includes(query))
      .slice(0, 20);
  }

  function getAncestors(branchId) {
    const ancestors = [];
    let current = branchesMap.get(branchId);

    // Start with the parent, not the selected branch itself
    if (current && current.parent_id) {
      current = branchesMap.get(current.parent_id);
    } else {
      // Selected branch has no parent, just return the default branch
      const root = Array.from(branchesMap.values()).find(b => b.is_default);
      return root ? [root] : [];
    }

    while (current && !current.is_default) {
      ancestors.unshift(current);
      if (!current.parent_id) break;
      current = branchesMap.get(current.parent_id);
    }

    const root = Array.from(branchesMap.values()).find(b => b.is_default);
    if (root) {
      ancestors.push(root);
    }

    return ancestors;
  }

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

  function buildDescendantTree(branchId, visited = new Set()) {
    if (visited.has(branchId)) return null;
    visited.add(branchId);

    const branch = branchesMap.get(branchId);
    if (!branch) return null;

    const children = branches
      .filter(b => b && b.parent_id === branchId)
      .map(child => buildDescendantTree(child.id, visited))
      .filter(c => c !== null);

    return {
      branch,
      children
    };
  }

  function calculateTreePositions(tree, startY, parentX) {
    if (!tree) return { nodes: [], lines: [] };

    const nodes = [];
    const lines = [];

    const height = calculateTreeHeight(tree);
    const width = calculateTreeWidth(tree);

    const centerX = parentX;
    const currentY = startY;

    // Current node
    nodes.push({
      branch: tree.branch,
      x: centerX,
      y: currentY,
      isSelected: tree.branch.id === selectedBranchId
    });

    // Children
    if (tree.children.length > 0) {
      const totalWidth = tree.children.length * HORIZONTAL_SPACING;
      const startX = centerX - totalWidth / 2 + HORIZONTAL_SPACING / 2;

      tree.children.forEach((child, idx) => {
        const childX = startX + idx * HORIZONTAL_SPACING;
        const childStartY = currentY + VERTICAL_SPACING;

        // Draw line from parent to child
        lines.push({
          x1: centerX,
          y1: currentY + CARD_HEIGHT / 2,
          x2: childX,
          y2: childStartY - CARD_HEIGHT / 2
        });

        const childResult = calculateTreePositions(
          child,
          childStartY,
          childX
        );

        nodes.push(...childResult.nodes);
        lines.push(...childResult.lines);
      });
    }

    return { nodes, lines };
  }

  function calculateTreeHeight(tree) {
    if (!tree || tree.children.length === 0) return CARD_HEIGHT;
    return (
      CARD_HEIGHT +
      VERTICAL_SPACING +
      Math.max(...tree.children.map(c => calculateTreeHeight(c)))
    );
  }

  function calculateTreeWidth(tree) {
    if (!tree || tree.children.length === 0) return CARD_WIDTH;
    return Math.max(
      CARD_WIDTH,
      tree.children.length * HORIZONTAL_SPACING
    );
  }

  function getSelectedBranchVisualization() {
    if (!selectedBranchId) return null;

    const selected = branchesMap.get(selectedBranchId);
    if (!selected) return null;

    const ancestors = getAncestors(selectedBranchId);
    const descendantTree = buildDescendantTree(selectedBranchId);

    // Reverse ancestors so root (default branch) appears first at the top
    const ancestorPath = [...ancestors].reverse();

    // Calculate SVG dimensions
    const ancestorHeight = ancestorPath.length * VERTICAL_SPACING;
    const descendantResult = descendantTree
      ? calculateTreePositions(descendantTree, 0, 0)
      : { nodes: [], lines: [] };
    const descendantHeight = descendantResult.nodes.length > 0
      ? Math.max(...descendantResult.nodes.map(n => n.y)) + CARD_HEIGHT + 40
      : 0;

    const totalHeight = ancestorHeight + CARD_HEIGHT + descendantHeight + 80;
    const totalWidth = 800;

    // Adjust all positions
    const ancestorOffset = VERTICAL_SPACING;
    const selectedY = ancestorHeight + ancestorOffset;
    const descendantYOffset = selectedY + CARD_HEIGHT + VERTICAL_SPACING;

    const nodes = [];
    const lines = [];

    // Add ancestors (from root down to parent of selected)
    ancestorPath.forEach((ancestor, idx) => {
      const y = ancestorOffset + idx * VERTICAL_SPACING;
      nodes.push({
        branch: ancestor,
        x: totalWidth / 2,
        y,
        isSelected: false
      });

      // Line from ancestor to next
      if (idx < ancestorPath.length - 1) {
        lines.push({
          x1: totalWidth / 2,
          y1: y + CARD_HEIGHT / 2,
          x2: totalWidth / 2,
          y2: y + VERTICAL_SPACING - CARD_HEIGHT / 2
        });
      } else {
        // Line from last ancestor to selected
        lines.push({
          x1: totalWidth / 2,
          y1: y + CARD_HEIGHT / 2,
          x2: totalWidth / 2,
          y2: selectedY - CARD_HEIGHT / 2
        });
      }
    });

    // Add selected branch
    nodes.push({
      branch: selected,
      x: totalWidth / 2,
      y: selectedY,
      isSelected: true
    });

    // Add descendants
    if (descendantTree && descendantTree.children.length > 0) {
      const childResult = calculateTreePositions(
        { branch: selected, children: descendantTree.children },
        descendantYOffset,
        totalWidth / 2
      );

      // Skip the first node (it's the selected branch we already added)
      childResult.nodes.forEach(node => {
        if (node.branch.id !== selectedBranchId) {
          nodes.push(node);
        }
      });

      // Add line from selected to first children
      descendantTree.children.forEach((child, idx) => {
        const totalWidth2 = descendantTree.children.length * HORIZONTAL_SPACING;
        const startX = totalWidth / 2 - totalWidth2 / 2 + HORIZONTAL_SPACING / 2;
        const childX = startX + idx * HORIZONTAL_SPACING;

        lines.push({
          x1: totalWidth / 2,
          y1: selectedY + CARD_HEIGHT / 2,
          x2: childX,
          y2: descendantYOffset - CARD_HEIGHT / 2
        });
      });

      lines.push(...childResult.lines.slice(descendantTree.children.length));
    }

    return { nodes, lines, width: totalWidth, height: totalHeight };
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
  const visualization = $derived(getSelectedBranchVisualization());
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

  <!-- Tree Visualization -->
  {#if visualization}
    <div class="tree-view">
      <svg
        bind:this={svgElement}
        width={visualization.width}
        height={visualization.height}
        class="tree-svg"
      >
        <!-- Draw connection lines -->
        <g class="connections">
          {#each visualization.lines as line (JSON.stringify(line))}
            <path
              d="M {line.x1} {line.y1} Q {(line.x1 + line.x2) / 2} {(line.y1 + line.y2) / 2}, {line.x2} {line.y2}"
              class="connection-line"
            />
          {/each}
        </g>

        <!-- Draw nodes -->
        <g class="nodes">
          {#each visualization.nodes as node (node.branch.id)}
            <g
              class="node"
              transform="translate({node.x - CARD_WIDTH / 2}, {node.y - CARD_HEIGHT / 2})"
            >
              <!-- Card background -->
              <rect
                width={CARD_WIDTH}
                height={CARD_HEIGHT}
                rx="6"
                class="card-bg"
                style="border-color: {getCardColor(node.branch)}"
              />

              <!-- Branch name -->
              <text
                x={CARD_WIDTH / 2}
                y="18"
                class="card-name"
                text-anchor="middle"
              >
                {node.branch.name.length > 18
                  ? node.branch.name.substring(0, 15) + '...'
                  : node.branch.name}
              </text>

              <!-- PR info -->
              <text x={CARD_WIDTH / 2} y="38" class="card-info" text-anchor="middle">
                {#if node.branch.is_default}
                  (default)
                {:else if !node.branch.pull_request}
                  (no PR)
                {:else}
                  #{node.branch.pull_request.number}
                {/if}
              </text>

              <!-- State badge -->
              <text
                x={CARD_WIDTH / 2}
                y="56"
                class="card-state"
                text-anchor="middle"
                style="fill: {getCardColor(node.branch)}"
              >
                {getStateLabel(node.branch)}
              </text>

              <!-- Click area -->
              <rect
                width={CARD_WIDTH}
                height={CARD_HEIGHT}
                rx="6"
                class="click-area"
                on:click={() => handleSelectBranch(node.branch.id)}
                on:keydown={(e) => {
                  if (e.key === 'Enter') handleSelectBranch(node.branch.id);
                }}
                role="button"
                tabindex="0"
              />
            </g>
          {/each}
        </g>
      </svg>

      <button class="close-btn" on:click={() => (selectedBranchId = null)}>
        ← Back to Search
      </button>
    </div>
  {/if}

  <!-- Empty State -->
  {#if !searchQuery && !visualization}
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
    overflow: auto;
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

  :global(.tree-view) {
    flex: 1;
    overflow: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    background: linear-gradient(135deg, #ffffff 0%, #f6f8fa 100%);
  }

  :global(.tree-svg) {
    display: block;
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  }

  :global(.connection-line) {
    stroke: #a371f7;
    stroke-width: 2;
    fill: none;
    stroke-linecap: round;
  }

  :global(.card-bg) {
    fill: #ffffff;
    stroke-width: 2;
  }

  :global(.card-name) {
    font-size: 12px;
    font-weight: 600;
    fill: #24292f;
    pointer-events: none;
  }

  :global(.card-info) {
    font-size: 10px;
    fill: #57606a;
    pointer-events: none;
  }

  :global(.card-state) {
    font-size: 10px;
    font-weight: 600;
    pointer-events: none;
  }

  :global(.click-area) {
    fill: transparent;
    cursor: pointer;
  }

  :global(.click-area:hover) {
    fill: rgba(0, 0, 0, 0.02);
  }

  :global(.close-btn) {
    margin-top: 16px;
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
