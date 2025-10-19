<script>
  import { onMount } from 'svelte';
  import Sidebar from './Sidebar.svelte';

  let { params = {} } = $props();
  let name = $derived(params.name || '');
  let repository = $derived(params.repository || '');
  let issues = $state([]);
  let page = $state(1);
  let paginationLinks = $state([]);

  let lastPage = $state(1);

  async function getIssues(page) {
    const res = await fetch(`/api/org/${name}/repo/${repository}/issues?page=${page}`);
    let json = await res.json();
    lastPage = json.lastPage;
    issues = json.data;
    paginationLinks = json.links;
  }

  onMount(async () => {
    await getIssues(page);
  });

</script>

<div class="repo-dashboard">
  <Sidebar {params} selectedSection="Issues" />
  <div class="repo-main">
    {#each issues as issue}
      <div class="issue">
        <h3>{issue.title}</h3>
      </div>
    {/each}

    {#if paginationLinks}
      <div class="pagination">
        {#each paginationLinks as link}
          <a on:click={() => getIssues(link.page)}>{link.page}</a>
        {/each}
      </div>
    {/if}
  </div>
</div>
  
<style>
  .repo-dashboard {
    height: 100%;

    display: flex;
    gap: 0.5rem;
    overflow: auto;
  }
</style>
