<script>
  import { onMount, untrack } from 'svelte';
  import Sidebar from '../sidebar/Sidebar.svelte';
  import SidebarGroup from '../sidebar/group.svelte';
  import Pagination from '../Pagination.svelte';
  import ListItem from '../ListItem.svelte';
  import LinkedPrItem from './LinkedPrItem.svelte';
  import ListItemSkeleton from '../ListItemSkeleton.svelte';
  import Select from '../Select.svelte';
  import PrNotice from './PrNotice.svelte';
  import { organization, repository, repoMetadata, waitForMetadata } from '../stores.js';

  let { params = {} } = $props();
  let type = $derived(params.type);

  let items = $state([]);
  let paginationLinks = $state([]);
  let currentPage = $state(1);
  let isLoading = $state(true);
  let branchesForNotice = $state([]);
  let selectableMilestones = $state([]);

  const GROUP_ORDER = ['needs_action', 'approved', 'pending', 'no_reviewers', 'draft', 'issues'];
  const GROUP_LABELS = {
    needs_action: 'Needs your action',
    approved: 'Approved',
    pending: 'Pending review',
    no_reviewers: 'No reviewers',
    draft: 'Draft',
    issues: 'Issues'
  };

  let shouldGroup = $derived(items.some(i => i.group));

  let groupedItems = $derived.by(() => {
    if (!shouldGroup || !items.length) return [];

    const groups = {};
    for (const item of items) {
      const group = item.group || 'issues';
      if (!groups[group]) groups[group] = [];
      groups[group].push(item);
    }

    return GROUP_ORDER
      .filter(key => groups[key]?.length)
      .map(key => ({ key, label: GROUP_LABELS[key], items: groups[key] }));
  });

  const POSSABLE_ITEM_STATES = [
    { value: 'open', label: 'Open' },
    { value: 'closed', label: 'Closed' },
    { value: 'all', label: 'All' }
  ];

  let state = $state('open');
  let assignees = $state([]);
  let selectedAssignee = $state(window.USER_ID);
  let searchQuery = $state('');
  let selectedMilestone = $state(null);

  const anyAssigneeOption = { value: 'any', label: 'Any' };

  async function getContributors() {
    const metadata = await waitForMetadata();
    assignees = metadata.assignees.filter(a => a).map(assignee => ({
      value: assignee.id,
      image: assignee.avatar_url,
      label: assignee.display_name,
    }));

    assignees.unshift(anyAssigneeOption);
  }

  async function getMilestones() {
    const metadata = await waitForMetadata();
    selectableMilestones = metadata.milestones.filter(m => m).map(milestone => ({
      value: milestone.id,
      label: milestone.title,
    }));
  }

  async function getItems(pageNr = 1) {
    isLoading = true;
    currentPage = pageNr;

    let url = `${route('organizations.repositories.items', {$organization, $repository})}?page=${pageNr}&state=${state}`;

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

    getBranchesForNotices();
  }

  async function getBranchesForNotices() {
    branchesForNotice = await api.get(route('organizations.repositories.branches.pr.notices', {$organization, $repository}));
  }

  function linkToNewItem(itemType) {
    window.location.hash = `#/${$organization}/${$repository}/new/${itemType}`;
  }

  function filterItem() {
    currentPage = 1;
    getItems(currentPage);
  }

  onMount(async () => {
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
    });
  });

</script>

<div class="repo-dashboard">
  <Sidebar>
    <button class="button-primary" type="button" onclick={() => linkToNewItem('issue')}>New Issue</button>
    <button class="button-primary-outline" type="button" onclick={() => linkToNewItem('pr')}>New Pull Request</button>

    <SidebarGroup title="State">
      <Select name="state" selectableItems={POSSABLE_ITEM_STATES} bind:selectedValue={state} onChange={() => { filterItem() }} searchable={false} />
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

      {#if shouldGroup && groupedItems.length}
        {#each groupedItems as group}
          <div class="group-header">
            <span class="group-label">{group.label}</span>
            <span class="group-count">{group.items.length}</span>
          </div>
          {#each group.items as item}
            <ListItem {item} />

            {#if item.linked_prs?.length > 0}
              <div class="linked-prs">
                {#each item.linked_prs as pr}
                  <LinkedPrItem {pr} />
                {/each}
              </div>
            {/if}
          {/each}
        {/each}
      {:else}
        {#each items as item}
          <ListItem {item} />

          {#if item.linked_prs?.length > 0}
            <div class="linked-prs">
              {#each item.linked_prs as pr}
                <LinkedPrItem {pr} />
              {/each}
            </div>
          {/if}
        {/each}
      {/if}

      {#if paginationLinks.length > 3}
        <Pagination links={paginationLinks} onSelect={(page) => getItems(page)} />
      {/if}
    {/if}
  </div>
</div>

<style lang="scss">
  @import '../../../scss/components/item-overview';
</style>
