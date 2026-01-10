<script>
  import { onMount, untrack } from 'svelte';
  import Sidebar from '../sidebar/Sidebar.svelte';
  import SidebarGroup from '../sidebar/group.svelte';
  import Pagination from '../Pagination.svelte';
  import ListItem from '../ListItem.svelte';
  import ListItemSkeleton from '../ListItemSkeleton.svelte';
  import Select from '../Select.svelte';
  import PrNotice from './PrNotice.svelte';
  import { organization, repository } from '../stores.js';

  let { params = {} } = $props();
  let type = $derived(params.type);

  let issues = $state([]);
  let paginationLinks = $state([]);
  let currentPage = $state(1);
  let isLoading = $state(true);
  let branchesForNotice = $state([]);

  const isPR= $derived(type === 'prs');

  let stateOptions = $state([]);

  let state = $state('open');
  let assignees = $state([]);
  let selectedAssignee = $state(window.USER_ID);
  let searchQuery = $state('');

  const anyAssigneeOption = { value: 'any', label: 'Any' };

  async function getContributors() {
    // Get the assnees and map them to the {value, label} format for Select component
    assignees = await api.get(route('organizations.repositories.contributors', {$organization, $repository}))
      .then(response => response.map(assignee => ({
        value: assignee.id,
        image: assignee.avatar_url,
        label: assignee.display_name,
      })
    ));

    assignees.unshift(anyAssigneeOption);

    const currentUserId = Number(window.USER_ID);
    if (currentUserId && assignees.some(a => a.value === currentUserId)) {
      selectedAssignee = currentUserId;
    }
  }


  async function getItems(pageNr = 1) {
    isLoading = true;
    currentPage = pageNr;

    let url = `${route('organizations.repositories.items', {$organization, $repository, type})}?page=${pageNr}&state=${state}`;
    url += `&assignee=${selectedAssignee}`;
    url += `&search=${searchQuery}`;

    const json = await api.get(url)
    issues = json.data
    paginationLinks = json.links
    
    for (let i = 0; i < issues.length; i++) {
      try {
        issues[i].labels = JSON.parse(issues[i].labels);
      } catch (e) {
        issues[i].labels = [];
      }
    }

    isLoading = false;

    if (isPR) {
      // Get branches applical for a PR
      getBranchesForNotices();
    }
  }

  async function getBranchesForNotices() {
    branchesForNotice = await api.get(route('organizations.repositories.branches.pr.notices', {$organization, $repository}));
  }

  function linkToNewItem(type) {
    window.location.hash = `#/${$organization}/${$repository}/new/${type}`;
  }

  function filterItem() {
    currentPage = 1;
    getItems(currentPage);
  }

  onMount(async () => {
    getContributors();
    getItems(currentPage);
  });

  $effect(() => {
    void type;
    void $organization;
    void $repository;

    untrack(() => {
      currentPage = 1;
      branchesForNotice = [];
      getItems(currentPage);

      if (type === 'prs') {
        stateOptions = [
          { value: 'open', label: 'Open' },
          { value: 'closed', label: 'Closed' },
          { value: 'merged', label: 'Merged' },
          { value: 'draft', label: 'Draft' },
          { value: 'all', label: 'All' }
        ]
      } else {
        stateOptions = [
          { value: 'open', label: 'Open' },
          { value: 'closed', label: 'Closed' },
          { value: 'all', label: 'All' }
        ];
      }
    });
  });

</script>

<div class="repo-dashboard">
  <Sidebar>
    <button class="button-primary" type="button" onclick={() => linkToNewItem(isPR ? 'pr' : 'issue')}>New {isPR ? 'Pull Request' : 'Issue'}</button>

    <SidebarGroup title="State">
      <Select name="state" selectableItems={stateOptions} bind:selectedValue={state} onChange={() => { filterItem() }} searchable={false} />
    </SidebarGroup>

    <SidebarGroup title="Assignees">
      <Select name="assignee" selectableItems={assignees} bind:selectedValue={selectedAssignee} onChange={() => { filterItem() }} />
    </SidebarGroup>
  </Sidebar>

  <div class="repo-main">
    <input type="text" class="search" placeholder="Search" bind:value={searchQuery} onblur={() => { filterItem() }} />

    {#if isLoading}
      {#each Array(3) as _}
        <ListItemSkeleton />
      {/each}
    {:else}

      {#each branchesForNotice as branch}
        <PrNotice item={branch} />
      {/each}


      {#each issues as item}
        <ListItem {item} />
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
