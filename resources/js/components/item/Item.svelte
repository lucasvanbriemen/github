<script>
  import { onMount } from 'svelte';
  import Sidebar from '../sidebar/Sidebar.svelte';
  import SidebarGroup from '../sidebar/group.svelte';
  import ItemSkeleton from '../ItemSkeleton.svelte';
  import ItemHeader from './ItemHeader.svelte';
  import FileTab from './FileTab.svelte';
  import Conversation from './Conversation.svelte';
  import Navigation from './Navigation.svelte';

  let { params = {} } = $props();
  let organization = $derived(params.organization || '');
  let repository = $derived(params.repository || '');
  let number = $derived(params.number || '');

  let item = $state({});
  let isPR = $state(false);
  let files = $state([]);
  let loadingFiles = $state(false);
  let activeTab = $state('conversation'); // 'conversation' or 'files'
  let isLoading = $state(true);

  onMount(async () => {
    isLoading = true;
    const res = await fetch(route(`organizations.repositories.item.show`, { organization, repository, number }));
    item = await res.json();

    try {
      item.labels = JSON.parse(item.labels);
    } catch (e) {
      item.labels = [];
    }

    isPR = item.type === 'pull_request';

    // If it's a PR, load the file diffs
    if (isPR) {
      loadFiles();
    }

    isLoading = false;
  });

  async function loadFiles() {
    loadingFiles = true;
    try {
      const res = await fetch(route(`organizations.repositories.item.files`, { organization, repository, number }));
      const data = await res.json();
      files = data || [];
    } catch (e) {
      console.error('Failed to load files:', e);
      files = [];
    } finally {
      loadingFiles = false;
    }
  }

  // Generate label style with proper color formatting
  function getLabelStyle(label) {
    return `background-color: #${label.color}4D; color: #${label.color}; border: 1px solid #${label.color};`;
  }
</script>

<div class="item-overview">
  <Sidebar {params} selectedDropdownSection="Issues">

    {#if !isLoading}
      <SidebarGroup title="Assignees">
        {#each item.assignees as assignee}
          <div class="assignee">
            <img src={assignee.avatar_url} alt={assignee.name} />
            <span>{assignee.display_name}</span>
          </div>
        {/each}
      </SidebarGroup>

      <SidebarGroup title="Labels">
        <div class="labels">
          {#each item.labels as label}
            <span class="label" style={getLabelStyle(label)}>
              {label.name}
            </span>
          {/each}
        </div>
      </SidebarGroup>

      {#if isPR}
        <SidebarGroup title="Reviewers">
          {#each item.requested_reviewers as reviewer}
            <div class="reviewer">
              <img src={reviewer.user.avatar_url} alt={reviewer.user.name} />
              <span>{reviewer.user.display_name}</span>
              <span>{reviewer.state}</span>
            </div>
          {/each}
        </SidebarGroup>
      {/if}
    {/if}
  </Sidebar>

  <!-- MAIN CONTENT: Header, Body, and Comments -->
  <div class="item-main">
    {#if isLoading}
      <ItemSkeleton />
    {:else}
      <ItemHeader {item} />

    <!-- PR Header: Branch Information (PR only) -->
    {#if isPR}
      <div class="item-header-pr">
        <span class="item-header-pr-title">
          <img src={item.opened_by?.avatar_url} alt={item.opened_by?.name} />
          {item.opened_by?.display_name} wants to merge
          {item.details.head_branch} into {item.details.base_branch}
        </span>
      </div>

      <Navigation bind:activeTab />
    {/if}

    <!-- Conversation Tab Content -->
    {#if !isPR || activeTab === 'conversation'}
      <Conversation {item} />
    {/if}

    <!-- Files Changed Tab Content (PR only) -->
    {#if isPR && activeTab === 'files'}
      <FileTab {params} files={files} loadingFiles={loadingFiles} />
    {/if}
    {/if}
  </div>
</div>

<style lang="scss">
  @import '../../../scss/components/item/item.scss';
</style>
