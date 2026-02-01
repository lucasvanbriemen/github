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

  let items = $state([]);
  let paginationLinks = $state([]);
  let currentPage = $state(1);
  let isLoading = $state(true);
  let branchesForNotice = $state([]);
  let repositoryMetadata = $state({});
  let selectableMilestones = $state([]);

  const isPR = $derived(type === 'prs');

  let stateOptions = $state([]);

  let state = $state('open');
  let assignees = $state([]);
  let selectedAssignee = $state(window.USER_ID);
  let searchQuery = $state('');
  let selectedMilestone = $state(null);

  const anyAssigneeOption = { value: 'any', label: 'Any' };

  async function getContributors() {
    assignees = repositoryMetadata.assignees.filter(a => a).map(assignee => ({
      value: assignee.id,
      image: assignee.avatar_url,
      label: assignee.display_name,
    }));

    assignees.unshift(anyAssigneeOption);
  }

  async function getMilestones() {
    selectableMilestones = repositoryMetadata.milestones.filter(m => m).map(milestone => ({
      value: milestone.id,
      label: milestone.title,
    }));
  }

  async function getItems(pageNr = 1) {
    isLoading = true;
    currentPage = pageNr;

    let url = `${route('organizations.repositories.items', {$organization, $repository, type})}?page=${pageNr}&state=${state}`;
    url += `&assignee=${selectedAssignee}`;
    url += `&search=${searchQuery}`;
    if (selectedMilestone) {
      url += `&milestone=${selectedMilestone}`;
    }

    const json = await api.get(url)
    items = json.data
    paginationLinks = json.links
    
    for (let i = 0; i < items.length; i++) {
      try {
        items[i].labels = JSON.parse(items[i].labels);
      } catch (e) {
        items[i].labels = [];
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
    repositoryMetadata = await api.get(route('organizations.repositories.metadata', { $organization, $repository }));
    getContributors();
    getMilestones();
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

    <SidebarGroup title="Milestone">
      <Select name="milestone" selectableItems={selectableMilestones} bind:selectedValue={selectedMilestone} onChange={() => { filterItem() }} searchable={false} />
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


      {#each items as item}
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
