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

  function buildHierarchy(flatBranches) {
    if (!flatBranches || flatBranches.length === 0) {
      return null;
    }

    // Find the root branch (no parent or default branch)
    const rootBranch = flatBranches.find(b => !b.parent_id || b.is_default);
    if (!rootBranch) {
      return null;
    }

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

    // Get width from container if available
    let containerWidth = svgWidth;
    if (containerElement?.clientWidth) {
      containerWidth = containerElement.clientWidth;
    }

    // Calculate height based on branch count
    const estimatedHeight = Math.max(600, branches.length * 120);
    svgHeight = estimatedHeight;

    const margin = { top: 40, right: 40, bottom: 40, left: 40 };
    const width = containerWidth - margin.left - margin.right;
    const height = estimatedHeight - margin.top - margin.bottom;

    // Create D3 hierarchy
    const root = hierarchy(hierarchyData);
    const treeLayout = tree().size([width, height]);
    treeLayout(root);

    // Clear previous content
    select(svgElement).selectAll('*').remove();

    // Set SVG dimensions
    svgWidth = containerWidth;

    const svg = select(svgElement)
      .attr('width', containerWidth)
      .attr('height', estimatedHeight);

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
      .attr('fill', d => {
        if (d.data.is_default) return '#1f6feb';
        if (!d.data.pull_request) return '#6e7681';
        const state = d.data.pull_request.state;
        if (state === 'open') return '#3fb950';
        if (state === 'merged') return '#a371f7';
        if (state === 'draft') return '#6e7681';
        if (state === 'closed') return '#da3633';
        return '#6e7681';
      })
      .style('cursor', d => d.data.pull_request ? 'pointer' : 'default')
      .style('stroke', '#ffffff')
      .style('stroke-width', 2)
      .on('click', (event, d) => {
        if (d.data.pull_request) {
          onNodeClick(d.data);
        }
      });

    // Add branch name labels
    nodes.append('text')
      .attr('x', 12)
      .attr('y', 0)
      .attr('dy', '0.31em')
      .style('font-size', '12px')
      .style('fill', '#24292f')
      .style('font-family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif')
      .style('pointer-events', 'none')
      .text(d => d.data.name);

    // Add PR badge if exists
    nodes.filter(d => d.data.pull_request)
      .append('text')
      .attr('x', 12)
      .attr('y', 16)
      .style('font-size', '10px')
      .style('fill', '#57606a')
      .style('font-family', 'monospace')
      .style('pointer-events', 'none')
      .text(d => `#${d.data.pull_request.number}`);
  }

  onMount(() => {
    // Set initial width from container
    if (containerElement?.clientWidth) {
      svgWidth = containerElement.clientWidth;
    }

    renderTree();

    // Re-render on window resize
    const handleResize = () => {
      if (containerElement?.clientWidth) {
        svgWidth = containerElement.clientWidth;
      }
      renderTree();
    };

    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  });

  // Re-render when branches change
  $effect(() => {
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
    background-color: #ffffff;
    display: flex;
    flex-direction: column;
  }

  :global(.branch-tree-container) {
    display: block;
    width: 100%;
    height: auto;
    min-height: 600px;
  }
</style>
