<script>
  import { onMount } from 'svelte';
  import Sidebar from './Sidebar.svelte';
  import Pagination from './Pagination.svelte';
  import ListItem from './ListItem.svelte';

  let { params = {} } = $props();
  let organization = $derived(params.organization || '');
  let repository = $derived(params.repository || '');
  let issues = $state([]);
  let paginationLinks = $state([]);
  let currentPage = $state(1);

  const path = window.location.pathname;
  let type = $derived(path.includes('/prs') ? 'pr' : 'issue');

  let state = $state('open');
  let assignees = $state([]);

  async function getItems(pageNr = 1, isInitialLoad = false) {
    currentPage = pageNr;

    let url = `${route('organizations.repositories.get', {organization, repository})}?page=${pageNr}&state=${state}`;
    if (assignees && (Array.isArray(assignees) ? assignees.length > 0 : true)) {
      const assigneeParam = Array.isArray(assignees) ? assignees.join(',') : assignees;
      url += `&assignee=${assigneeParam}`;
    }

    if (isInitialLoad) {
      url += `&isInitialLoad=true`;
    }

    const res = await fetch(url);
    let json = await res.json();
    issues = json.data;

    for (let i = 0; i < issues.length; i++) {
      issues[i].labels = JSON.parse(issues[i].labels);
    }

    paginationLinks = json.links;
  }

  function filterIssue(event) {
    state = event.detail.state;
    assignees = event.detail.assignees;

    currentPage = 1;
    getItems(currentPage);
  }

  onMount(async () => {
    await getItems(currentPage, true);
  });

</script>

<div class="repo-dashboard">
  <Sidebar {params} selectedDropdownSection="Issuses" showDetailsFrom="item-list" on:filterChange={filterIssue} />
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
