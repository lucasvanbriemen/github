<script>
  import { hierarchy, tree } from 'd3-hierarchy';
  import { select } from 'd3-selection';
  import { linkVertical } from 'd3-shape';
  import { onMount } from 'svelte';

  let { branches = [], onNodeClick = () => {} } = $props();

  let svgElement = $state(null);
  let dimensions = $state({ width: 800, height: 600 });

  function buildHierarchy(flatBranches) {
    if (!flatBranches || flatBranches.length === 0) {
      return null;
    }

    // Find the root branch (no parent or default branch)
    const rootBranch = flatBranches.find(b => !b.parent_id || b.is_default);
    if (!rootBranch) {
      return null;
    }

    // Create a map for quick lookup
    const branchMap = new Map(flatBranches.map(b => [b.id, b]));

    // Build children recursively
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
    if (!svgElement || !branches || branches.length === 0) {
      return;
    }

    const hierarchyData = buildHierarchy(branches);
    if (!hierarchyData) {
      return;
    }

    // Calculate dimensions based on branch count
    const estimatedHeight = Math.max(600, branches.length * 100);
    dimensions.height = estimatedHeight;

    const margin = { top: 40, right: 20, bottom: 40, left: 20 };
    const width = dimensions.width - margin.left - margin.right;
    const height = dimensions.height - margin.top - margin.bottom;

    // Create D3 hierarchy
    const root = hierarchy(hierarchyData);
    const treeLayout = tree().size([width, height]);
    treeLayout(root);

    // Clear previous content
    select(svgElement).selectAll('*').remove();

    const svg = select(svgElement)
      .attr('width', dimensions.width)
      .attr('height', dimensions.height)
      .attr('viewBox', [0, 0, dimensions.width, dimensions.height]);

    const g = svg.append('g')
      .attr('transform', `translate(${margin.left},${margin.top})`);

    // Draw links (branch relationships)
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

    // Draw nodes
    const nodes = g.selectAll('.node')
      .data(root.descendants())
      .enter()
      .append('g')
      .attr('class', 'branch-node')
      .attr('transform', d => `translate(${d.x},${d.y})`);

    // Add circles for nodes
    nodes.append('circle')
      .attr('r', 6)
      .attr('class', d => {
        if (d.data.is_default) return 'node-default';
        if (!d.data.pull_request) return 'node-no-pr';
        if (d.data.pull_request.state === 'open') return 'node-open';
        if (d.data.pull_request.state === 'merged') return 'node-merged';
        if (d.data.pull_request.state === 'draft') return 'node-draft';
        if (d.data.pull_request.state === 'closed') return 'node-closed';
        return 'node-default';
      })
      .attr('fill', d => {
        if (d.data.is_default) return '#1f6feb';
        if (!d.data.pull_request) return '#6e7681';
        if (d.data.pull_request.state === 'open') return '#3fb950';
        if (d.data.pull_request.state === 'merged') return '#a371f7';
        if (d.data.pull_request.state === 'draft') return '#6e7681';
        if (d.data.pull_request.state === 'closed') return '#da3633';
        return '#6e7681';
      })
      .style('cursor', d => d.data.pull_request ? 'pointer' : 'default')
      .on('click', (event, d) => {
        if (d.data.pull_request) {
          onNodeClick(d.data);
        }
      });

    // Add branch name labels
    nodes.append('text')
      .attr('class', 'branch-label')
      .attr('x', 12)
      .attr('y', 0)
      .attr('dy', '0.31em')
      .attr('font-size', '12px')
      .attr('fill', '#24292f')
      .text(d => d.data.name);

    // Add PR badge if exists
    nodes.filter(d => d.data.pull_request)
      .append('text')
      .attr('class', 'pr-badge')
      .attr('x', 12)
      .attr('y', 16)
      .attr('font-size', '10px')
      .attr('fill', '#57606a')
      .text(d => `#${d.data.pull_request.number}`);
  }

  onMount(() => {
    renderTree();

    // Re-render on window resize
    const handleResize = () => {
      dimensions.width = svgElement?.parentElement?.clientWidth || 800;
      renderTree();
    };

    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  });

  $effect(() => {
    if (branches && svgElement) {
      renderTree();
    }
  });
</script>

<div class="branch-tree-wrapper">
  <svg bind:this={svgElement} class="branch-tree-container"></svg>
</div>

<style>
  :global(.branch-tree-wrapper) {
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: #ffffff;
  }

  :global(.branch-tree-container) {
    display: block;
    margin: 0 auto;
  }

  :global(.branch-link) {
    fill: none;
    stroke: #d1d5da;
    stroke-width: 2;
  }

  :global(.branch-node circle) {
    stroke: #ffffff;
    stroke-width: 2;
  }

  :global(.branch-node circle:hover) {
    filter: brightness(1.1);
  }

  :global(.node-default) {
    fill: #1f6feb;
  }

  :global(.node-open) {
    fill: #3fb950;
  }

  :global(.node-merged) {
    fill: #a371f7;
  }

  :global(.node-draft) {
    fill: #6e7681;
  }

  :global(.node-closed) {
    fill: #da3633;
  }

  :global(.node-no-pr) {
    fill: #6e7681;
  }

  :global(.branch-label) {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
    font-size: 12px;
    fill: #24292f;
    pointer-events: none;
  }

  :global(.pr-badge) {
    font-family: monospace;
    font-size: 10px;
    fill: #57606a;
    pointer-events: none;
  }
</style>
