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

  const CARD_WIDTH = 200;
  const CARD_HEIGHT = 80;

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

    // Much larger height for vertical spacing, much larger width for horizontal spread
    const estimatedHeight = Math.max(800, branches.length * 150);
    svgHeight = estimatedHeight;

    const margin = { top: 60, right: 60, bottom: 60, left: 60 };
    // Make width much larger relative to height for horizontal spread
    const width = Math.max(containerWidth - margin.left - margin.right, 2000);
    const height = estimatedHeight - margin.top - margin.bottom;

    console.log('[BranchTree] Dimensions:', { width, height });

    const root = hierarchy(hierarchyData);
    // Use much larger width for horizontal spread, smaller height for vertical compression
    const treeLayout = tree().size([width, height * 0.6]);
    treeLayout(root);

    select(svgElement).selectAll('*').remove();
    svgWidth = containerWidth;

    const svg = select(svgElement)
      .attr('width', width + margin.left + margin.right)
      .attr('height', estimatedHeight);

    const g = svg.append('g')
      .attr('transform', `translate(${margin.left},${margin.top})`);

    // Draw links with curve
    const linkGenerator = linkVertical()
      .x(d => d.x)
      .y(d => d.y * (height / (height * 0.6)));

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
      .attr('transform', d => {
        const yAdjusted = d.y * (height / (height * 0.6));
        return `translate(${d.x - CARD_WIDTH / 2},${yAdjusted - CARD_HEIGHT / 2})`;
      });

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
      .attr('y', 20)
      .style('font-size', '13px')
      .style('font-weight', 'bold')
      .style('fill', '#24292f')
      .style('font-family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif')
      .style('pointer-events', 'none')
      .style('text-anchor', 'middle')
      .style('dominant-baseline', 'middle')
      .text(d => {
        // Truncate long branch names
        const name = d.data.name;
        return name.length > 20 ? name.substring(0, 17) + '...' : name;
      });

    // Draw PR info or status
    nodes.append('text')
      .attr('class', 'card-info')
      .attr('x', CARD_WIDTH / 2)
      .attr('y', 45)
      .style('font-size', '11px')
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
      .attr('y', 65)
      .style('font-size', '10px')
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
