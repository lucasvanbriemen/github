<script>
  import { onMount } from 'svelte';
  import ItemSkeleton from './ItemSkeleton.svelte';
  import ItemHeader from './ItemHeader.svelte';
  import FileTab from './FileTabWithSyntaxHighlighting.svelte';
  import Conversation from './Conversation.svelte';
  import Navigation from './Navigation.svelte';
  import Sidebar from './Sidebar.svelte';

  let { params = {} } = $props();
  let organization = $derived(params.organization || '');
  let repository = $derived(params.repository || '');
  let number = $derived(params.number || '');

  let item = $state({});
  let isPR = $state(false);
  let files = $state([]);
  let loadingFiles = $state(false);
  let activeTab = $state('conversation');
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
    const res = await fetch(route(`organizations.repositories.item.files`, { organization, repository, number }));
    const data = await res.json();
    files = data || [];
    loadingFiles = false;
  }
</script>

<div class="item-overview">
  <Sidebar {item} {isPR} {isLoading} {params} />

  <!-- MAIN CONTENT: Header, Body, and Comments -->
  <div class="item-main">
    {#if isLoading}
      <ItemSkeleton />
    {:else}
      <ItemHeader {item} />

    <!-- PR Header: Branch Information (PR only) -->
    {#if isPR}
      {#if activeTab === 'conversation'}
        <div class="item-header-pr">
          <span class="item-header-pr-title">
            <img src={item.opened_by?.avatar_url} alt={item.opened_by?.name} />
            {item.opened_by?.display_name} wants to merge
            {item.details.head_branch} into {item.details.base_branch}
          </span>
        </div>
      {/if}

      <Navigation bind:activeTab />
    {/if}

    <!-- Conversation Tab Content -->
    {#if !isPR || activeTab === 'conversation'}
      <Conversation {item} {params} />
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
