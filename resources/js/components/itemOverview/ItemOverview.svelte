<script>
  import { onMount } from 'svelte';
  import Sidebar from '../sidebar/Sidebar.svelte';
  import SidebarGroup from '../sidebar/group.svelte';
  import Pagination from '../Pagination.svelte';
  import ListItem from './ListItem.svelte';
  import ListItemSkeleton from './ListItemSkeleton.svelte';
  import Select from '../Select.svelte';
  import PrNotice from './PrNotice.svelte';

  let { params = {} } = $props();
  let organization = $derived(params.organization || '');
  let repository = $derived(params.repository || '');
  let issues = $state([]);
  let paginationLinks = $state([]);
  let currentPage = $state(1);
  let isLoading = $state(true);
  let branchesForNotice = $state([]);

  const path = window.location.hash;
  const type = $derived(path.includes('/prs') ? 'pr' : 'issue');
  const isPR= $derived(type === 'pr');
  const activeItem = $derived(isPR ? 'Pull Requests' : 'Issues');

  const stateOptions = [
    { value: 'open', label: 'Open' },
    { value: 'closed', label: 'Closed' },
    { value: 'all', label: 'All' }
  ];

  let state = $state('open');
  let assignees = $state([]);
  let selectedAssignee = $state(window.USER_ID);

  async function getContributors() {
    const res = await fetch(`${route('organizations.repositories.contributors.get', {organization, repository})}`);
    assignees = await res.json();

    // We have to format it into the {value, label} format for Select component
    assignees = assignees.map(assignee => ({
      value: assignee.id,
      label: assignee.display_name,
    }));

    const currentUserId = Number(window.USER_ID);
    if (currentUserId && assignees.some(a => a.value === currentUserId)) {
      selectedAssignee = currentUserId;
    }
  }


  async function getItems(pageNr = 1, isInitialLoad = false) {
    isLoading = true;
    currentPage = pageNr;

    let url = `${route('organizations.repositories.items.get', {organization, repository, type})}?page=${pageNr}&state=${state}`;
    url += `&assignee=${selectedAssignee}`;

    const res = await fetch(url);
    let json = await res.json();
    issues = json.data;

    for (let i = 0; i < issues.length; i++) {
      try {
        issues[i].labels = JSON.parse(issues[i].labels);
      } catch (e) {
        issues[i].labels = [];
      }
    }

    paginationLinks = json.links;
    isLoading = false;

    if (isPR) {
      // Get branches applical for a PR
      getBranchesForNotices();
    }
  }

  async function getBranchesForNotices() {
    const res = await fetch(route('organizations.repositories.branches.pr.notices', {organization, repository}));
    branchesForNotice = await res.json();
  }

  function linkToNewItem(type) {
    window.location.hash = `#/${organization}/${repository}/new/${type}`;
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
  <Sidebar {params} {activeItem}>
    <button class="button-primary" type="button" onclick={() => linkToNewItem(isPR ? 'pr' : 'issue')}>New {isPR ? 'Pull Request' : 'Issue'}</button>

    <SidebarGroup title="State">
      <Select name="state" selectableItems={stateOptions} bind:selectedValue={state} onChange={() => { filterItem() }}/>
    </SidebarGroup>

    <SidebarGroup title="Assignees">
      <Select name="assignee" selectableItems={assignees} bind:selectedValue={selectedAssignee} onChange={() => { filterItem() }} searchable={true} />
    </SidebarGroup>
  </Sidebar>

  <div class="repo-main">
    {#if isLoading}
      {#each Array(3) as _}
        <ListItemSkeleton />
      {/each}
    {:else}

      {#each branchesForNotice as branch}
        <PrNotice item={branch} {params} />
      {/each}


      {#each issues as item}
        <ListItem {item} itemType="issue" />
      {/each}

      {#if paginationLinks.length > 3}
        <Pagination links={paginationLinks} onSelect={(page) => getItems(page)} />
      {/if}
    {/if}
  </div>
</div>
  
<style lang="scss">
  @import '../../../scss/components/item-overview';
</style>
