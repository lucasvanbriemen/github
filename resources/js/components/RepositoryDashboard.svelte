<script>
  import { onMount } from 'svelte';
  import Sidebar from './Sidebar.svelte';

  let { params = {} } = $props();
  let organizations = [];
  let name = $derived(params.name || '');
  let repository = $derived(params.repository || '');

  onMount(async () => {
    const res = await fetch('/api/organizations');
    organizations = await res.json();
  });

</script>

<div class="repo-dashboard">
  <Sidebar {params} />
  <div class="repo-main">
    <!-- Placeholder main area for repository dashboard / issues / PRs routes -->
  </div>
</div>
  
<style>
  .repo-dashboard {
    height: 100%;
  }
</style>
