<script>
  import { hierarchy, tree } from 'd3-hierarchy';
  import { select } from 'd3-selection';
  import { linkVertical } from 'd3-shape';
  import { onMount } from 'svelte';

  let { branches = [], onNodeClick = () => {} } = $props();

  let svgElement = $state(null);
  let containerElement = $state(null);
  let svgWidth = $state(800);
  let svgHeight = $state(600);

  const CARD_WIDTH = 160;
  const CARD_HEIGHT = 70;
  const CARD_SPACING_HORIZONTAL = 280; // Space between cards at same level
  const CARD_SPACING_VERTICAL = 120; // Space between parent and child

  function getCardColor(node) {
    if (node.is_default) return '#1f6feb'; // Blue for default
    if (!node.pull_request) return '#6e7681'; // Gray for no PR

    const state = node.pull_request.state;
    if (state === 'open') return '#3fb950'; // Green for open
    if (state === 'merged') return '#a371f7'; // Purple for merged
    if (state === 'draft') return '#6e7681'; // Gray for draft
    if (state === 'closed') return '#da3633'; // Red for closed
    return '#6e7681'; // Default gray
  }

  function buildHierarchy(flatBranches) {
    if (!flatBranches || flatBranches.length === 0) {
      return null;
    }

    console.log('[BranchTree] Building hierarchy from branches:', {
      total: flatBranches.length,
    });

    const defaults = flatBranches.filter(b => b.is_default);
    console.log('[BranchTree] Default branches:', defaults.length);

    const rootBranch = flatBranches.find(b => !b.parent_id || b.is_default);
    console.log('[BranchTree] Root branch found:', rootBranch?.name);

    if (!rootBranch) {
      console.log('[BranchTree] ERROR: No root branch found!');
      return null;
    }

    function addChildren(branch) {
      const children = flatBranches.filter(b => b.parent_id === branch.id);
      return {
        ...branch,
        children: children.length > 0 ? children.map(addChildren) : [],
      };
    }

    return addChildren(rootBranch);
  }

  function renderTree() {
    console.log('[BranchTree] renderTree called');

    if (!svgElement || !branches || branches.length === 0) {
      console.log('[BranchTree] renderTree aborted: missing svgElement or branches');
      return;
    }

    const hierarchyData = buildHierarchy(branches);
    if (!hierarchyData) {
      console.log('[BranchTree] renderTree aborted: no hierarchy data');
      return;
    }

    // Get width from container
    let containerWidth = svgWidth;
    if (containerElement?.clientWidth) {
      containerWidth = containerElement.clientWidth;
    }

    // Calculate dimensions based on tree structure
    const root = hierarchy(hierarchyData);
    const treeDepth = root.height + 1; // Depth of the tree
    const treeWidth = root.descendants().filter(d => !d.children || d.children.length === 0).length; // Count leaf nodes

    // Width: ensure enough space for all siblings at widest level
    const calculatedWidth = Math.max(
      containerWidth - 120,
      treeWidth * CARD_SPACING_HORIZONTAL + 200
    );

    // Height: much more compact - only space for depth
    const estimatedHeight = Math.max(600, treeDepth * CARD_SPACING_VERTICAL + 200);
    svgHeight = estimatedHeight;

    const margin = { top: 40, right: 40, bottom: 40, left: 40 };
    const width = calculatedWidth - margin.left - margin.right;
    const height = estimatedHeight - margin.top - margin.bottom;

    console.log('[BranchTree] Dimensions:', {
      width,
      height,
      treeDepth,
      treeWidth,
      estimatedHeight
    });

    // Apply layout with proper spacing
    const treeLayout = tree().size([width, height]);
    treeLayout(root);

    select(svgElement).selectAll('*').remove();

    const svg = select(svgElement)
      .attr('width', width + margin.left + margin.right)
      .attr('height', estimatedHeight);

    const g = svg.append('g')
      .attr('transform', `translate(${margin.left},${margin.top})`);

    // Draw links with curve
    const linkGenerator = linkVertical()
      .x(d => d.x)
      .y(d => d.y);

    g.selectAll('.link')
      .data(root.links())
      .enter()
      .append('path')
      .attr('class', 'branch-link')
      .attr('d', linkGenerator)
      .attr('stroke', '#d1d5da')
      .attr('stroke-width', 2)
      .attr('fill', 'none');

    // Draw card nodes
    const descendants = root.descendants();
    console.log('[BranchTree] Drawing nodes:', descendants.length);

    const nodes = g.selectAll('.node')
      .data(descendants)
      .enter()
      .append('g')
      .attr('class', 'branch-node')
      .attr('transform', d => `translate(${d.x - CARD_WIDTH / 2},${d.y - CARD_HEIGHT / 2})`);

    // Draw card background
    nodes.append('rect')
      .attr('class', 'card-bg')
      .attr('width', CARD_WIDTH)
      .attr('height', CARD_HEIGHT)
      .attr('rx', 6)
      .attr('ry', 6)
      .style('fill', '#ffffff')
      .style('stroke', d => getCardColor(d.data))
      .style('stroke-width', 3)
      .style('box-shadow', '0 1px 3px rgba(0,0,0,0.1)');

    // Draw branch name (bold, centered at top)
    nodes.append('text')
      .attr('class', 'card-title')
      .attr('x', CARD_WIDTH / 2)
      .attr('y', 16)
      .style('font-size', '11px')
      .style('font-weight', 'bold')
      .style('fill', '#24292f')
      .style('font-family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif')
      .style('pointer-events', 'none')
      .style('text-anchor', 'middle')
      .style('dominant-baseline', 'middle')
      .text(d => {
        // Truncate long branch names
        const name = d.data.name;
        return name.length > 16 ? name.substring(0, 13) + '...' : name;
      });

    // Draw PR info or status
    nodes.append('text')
      .attr('class', 'card-info')
      .attr('x', CARD_WIDTH / 2)
      .attr('y', 35)
      .style('font-size', '9px')
      .style('fill', '#57606a')
      .style('font-family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif')
      .style('pointer-events', 'none')
      .style('text-anchor', 'middle')
      .style('dominant-baseline', 'middle')
      .text(d => {
        if (d.data.is_default) return '(default)';
        if (!d.data.pull_request) return '(no PR)';
        return `#${d.data.pull_request.number}`;
      });

    // Draw PR state badge
    nodes.append('text')
      .attr('class', 'card-state')
      .attr('x', CARD_WIDTH / 2)
      .attr('y', 52)
      .style('font-size', '9px')
      .style('fill', d => getCardColor(d.data))
      .style('font-weight', 'bold')
      .style('font-family', 'monospace')
      .style('pointer-events', 'none')
      .style('text-anchor', 'middle')
      .style('dominant-baseline', 'middle')
      .text(d => {
        if (d.data.is_default) return '●';
        if (!d.data.pull_request) return '○';
        const state = d.data.pull_request.state;
        if (state === 'open') return '● OPEN';
        if (state === 'merged') return '✓ MERGED';
        if (state === 'draft') return '◐ DRAFT';
        if (state === 'closed') return '✕ CLOSED';
        return '●';
      });

    // Make cards clickable if they have a PR
    nodes.style('cursor', d => d.data.pull_request ? 'pointer' : 'default')
      .on('click', (event, d) => {
        if (d.data.pull_request) {
          onNodeClick(d.data);
        }
      });
  }

  onMount(() => {
    console.log('[BranchTree] onMount called');
    if (containerElement?.clientWidth) {
      svgWidth = containerElement.clientWidth;
    }

    renderTree();

    const handleResize = () => {
      if (containerElement?.clientWidth) {
        svgWidth = containerElement.clientWidth;
      }
      renderTree();
    };

    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  });

  $effect(() => {
    console.log('[BranchTree] Effect triggered with branches:', branches?.length);
    if (branches.length > 0) {
      renderTree();
    }
  });
</script>

<div class="branch-tree-wrapper" bind:this={containerElement}>
  <svg bind:this={svgElement} class="branch-tree-container" width={svgWidth} height={svgHeight}></svg>
</div>

<style>
  :global(.branch-tree-wrapper) {
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: #f6f8fa;
    display: flex;
    flex-direction: column;
  }

  :global(.branch-tree-container) {
    display: block;
    width: 100%;
    height: auto;
    min-height: 600px;
    background-color: #f6f8fa;
  }

  :global(.branch-link) {
    fill: none;
    stroke: #d1d5da;
    stroke-width: 2;
  }

  :global(.branch-node:hover .card-bg) {
    filter: brightness(0.98);
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
  }

  :global(.branch-node:hover .card-title) {
    font-weight: bold;
  }
</style>
