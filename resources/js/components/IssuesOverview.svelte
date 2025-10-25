<script>
  import { onMount } from 'svelte';
  import Sidebar from './Sidebar.svelte';
  import Pagination from './Pagination.svelte';
  import ListItem from './ListItem.svelte';

  let { params = {} } = $props();
  let name = $derived(params.name || '');
  let repository = $derived(params.repository || '');
  let issues = $state([]);
  let paginationLinks = $state([]);

  async function getIssues(pageNr) {
    const res = await fetch(`/api/org/${name}/repo/${repository}/issues?page=${pageNr}`);
    let json = await res.json();
    issues = json.data;

    for (let i = 0; i < issues.length; i++) {
      issues[i].labels = JSON.parse(issues[i].labels);
    }

    paginationLinks = json.links;
  }

  onMount(async () => {
    await getIssues(1);
  });

</script>

<div class="repo-dashboard">
  <Sidebar {params} selectedSection="Issues" />
  <div class="repo-main">
    {#each issues as item}
      <ListItem {item} itemType="issue" />
    {/each}

    {#if paginationLinks.length > 3}
      <Pagination links={paginationLinks} onSelect={(page) => getIssues(page)} />
    {/if}
  </div>
</div>
  
<style>
  .repo-dashboard {
    height: 100%;
    width: 100%;

    display: flex;
    gap: 1rem;
    overflow: auto;

    .repo-main {
      width: calc(85vw - 3rem);
      display: flex;
      flex-direction: column;
      gap: 1rem;
      margin-top: 1rem;
    }
  }
</style>
