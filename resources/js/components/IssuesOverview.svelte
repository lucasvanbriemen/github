<script>
  import { onMount } from 'svelte';
  import Sidebar from './Sidebar.svelte';

  let { params = {} } = $props();
  let name = $derived(params.name || '');
  let repository = $derived(params.repository || '');
  let issues = $state([]);
  let page = $state(1);

  onMount(async () => {
    const res = await fetch(`/api/org/${name}/repo/${repository}/issues?page=${page}`);
    issues = await res.json();
  });

</script>

<div class="repo-dashboard">
  <Sidebar {params} selectedSection="Issues" />
  <div class="repo-main">
    {#each issues as issue}
      <div class="issue">
        <h3>{issue.title}</h3>
        <p>{issue.body}</p>
      </div>
    {/each}
  </div>
</div>
  
<style>
  .repo-dashboard {
    height: 100%;
  }
</style>
