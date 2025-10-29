<script>
  import { onMount } from 'svelte';
  import Sidebar from './Sidebar.svelte';

  let { params = {} } = $props();
</script>

<div class="repo-dashboard">
  <Sidebar {params} selectedDropdownSection="Home" showDetailsFrom="repo-dashboard" />
  <div class="repo-main">
  </div>
</div>
  
<style>
  .repo-dashboard {
    height: 100%;
  }
</style>
