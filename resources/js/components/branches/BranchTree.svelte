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

    console.log('[BranchTree] Building hierarchy from branches:', {
      total: flatBranches.length,
      sample: flatBranches.slice(0, 3),
    });

    // Check for branches with no parent
    const noParent = flatBranches.filter(b => !b.parent_id);
    console.log('[BranchTree] Branches with no parent_id:', noParent.length, noParent.slice(0, 3));

    // Check for default branches
    const defaults = flatBranches.filter(b => b.is_default);
    console.log('[BranchTree] Default branches:', defaults.length, defaults);

    // Find the root branch (no parent or default branch)
    const rootBranch = flatBranches.find(b => !b.parent_id || b.is_default);
    console.log('[BranchTree] Root branch found:', rootBranch);

    if (!rootBranch) {
      console.log('[BranchTree] ERROR: No root branch found! First few branches:', flatBranches.slice(0, 5));
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
    console.log('[BranchTree] renderTree called with:', {
      svgElement: !!svgElement,
      branches: branches?.length,
      containerElement: !!containerElement,
    });

    if (!svgElement || !branches || branches.length === 0) {
      console.log('[BranchTree] renderTree aborted: missing svgElement or branches');
      return;
    }

    const hierarchyData = buildHierarchy(branches);
    console.log('[BranchTree] Hierarchy data:', hierarchyData);
    if (!hierarchyData) {
      console.log('[BranchTree] renderTree aborted: no hierarchy data');
      return;
    }

    // Get width from container if available
    let containerWidth = svgWidth;
    if (containerElement?.clientWidth) {
      containerWidth = containerElement.clientWidth;
      console.log('[BranchTree] Container width:', containerWidth);
    }

    // Calculate height based on branch count
    const estimatedHeight = Math.max(600, branches.length * 120);
    svgHeight = estimatedHeight;

    const margin = { top: 40, right: 40, bottom: 40, left: 40 };
    const width = containerWidth - margin.left - margin.right;
    const height = estimatedHeight - margin.top - margin.bottom;

    console.log('[BranchTree] Dimensions:', {
      containerWidth,
      estimatedHeight,
      width,
      height,
    });

    // Create D3 hierarchy
    const root = hierarchy(hierarchyData);
    const treeLayout = tree().size([width, height]);
    treeLayout(root);
    console.log('[BranchTree] D3 root created with descendants:', root.descendants().length);

    // Clear previous content
    select(svgElement).selectAll('*').remove();

    // Set SVG dimensions
    svgWidth = containerWidth;

    const svg = select(svgElement)
      .attr('width', containerWidth)
      .attr('height', estimatedHeight);

    console.log('[BranchTree] SVG element:', { containerWidth, estimatedHeight });

    const g = svg.append('g')
      .attr('transform', `translate(${margin.left},${margin.top})`);

    // Draw links (branch relationships)
    const linkGenerator = linkVertical()
      .x(d => d.x)
      .y(d => d.y);

    const links = root.links();
    console.log('[BranchTree] Drawing links:', links.length);

    g.selectAll('.link')
      .data(links)
      .enter()
      .append('path')
      .attr('class', 'branch-link')
      .attr('d', linkGenerator)
      .attr('stroke', '#d1d5da')
      .attr('stroke-width', 2)
      .attr('fill', 'none');

    // Draw nodes
    const descendants = root.descendants();
    console.log('[BranchTree] Drawing nodes:', descendants.length);

    const nodes = g.selectAll('.node')
      .data(descendants)
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
    console.log('[BranchTree] onMount called');
    // Set initial width from container
    if (containerElement?.clientWidth) {
      svgWidth = containerElement.clientWidth;
      console.log('[BranchTree] Set svgWidth from container:', svgWidth);
    } else {
      console.log('[BranchTree] Container not available on mount');
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
