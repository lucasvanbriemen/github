<script>
  import { onMount } from 'svelte';
  import Sidebar from './Sidebar.svelte';
  import Pagination from './Pagination.svelte';
  import ListItem from './ListItem.svelte';
  import SearchSelect from './SearchSelect.svelte';

  let { params = {} } = $props();
  let organization = $derived(params.organization || '');
  let repository = $derived(params.repository || '');
  let issues = $state([]);
  let paginationLinks = $state([]);
  let currentPage = $state(1);

  const path = window.location.hash;
  const type = $derived(path.includes('/prs') ? 'pr' : 'issue');
  const selectedDropdownSection = $derived(type === 'issue' ? 'Issues' : 'Pull Requests');

  const stateOptions = [
    { value: 'open', label: 'Open' },
    { value: 'closed', label: 'Closed' },
    { value: 'all', label: 'All' }
  ];

  let state = $state('open');
  let assignees = $state(window.USER_ID);
  let selectedAssignee = $state([]);

  async function getContributors() {
    const res = await fetch(`${route('organizations.repositories.get.contributors', {organization, repository})}`);
    assignees = await res.json();

    // We have to format it into the {value, label} format for SearchSelect
    assignees = assignees.map(assignee => ({
      value: assignee.id,
      label: assignee.name
    }));

    const currentUserId = Number(window.USER_ID);
    if (currentUserId && assignees.some(a => a.value === currentUserId)) {
      selectedAssignee = [currentUserId];
    }
  }


  async function getItems(pageNr = 1, isInitialLoad = false) {
    currentPage = pageNr;

    let url = `${route('organizations.repositories.items.get', {organization, repository, type})}?page=${pageNr}&state=${state}`;
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

  function filterItem() {
    currentPage = 1;
    getItems(currentPage);
  }

  onMount(async () => {
    getContributors();
    getItems(currentPage, true);
  });

</script>

<div class="repo-dashboard">
  <Sidebar {params} selectedDropdownSection={selectedDropdownSection}>
    <SearchSelect
      name="state"
      options={stateOptions}
      bind:value={state}
      on:change={() => {
        filterItem()
      }}
    />

    <SearchSelect
      name="assignee"
      options={assignees}
      bind:value={selectedAssignee}
      on:change={() => {
        filterItem();
      }}
      multiple={true}
    />
  </Sidebar>

  <div class="repo-main">
    {#each issues as item}
      <ListItem {item} itemType="issue" />
    {/each}

    {#if paginationLinks.length > 3}
      <Pagination links={paginationLinks} onSelect={(page) => getItems(page)} />
    {/if}
  </div>
</div>
  
<style lang="scss">
  @import '../../scss/components/item-overview' ;
</style>
