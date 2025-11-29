<script>
  import { onMount } from 'svelte';
  import ItemSkeleton from './ItemSkeleton.svelte';
  import ItemHeader from './ItemHeader.svelte';
  import FileTab from './pr/FileTab.svelte';
  import Conversation from './Conversation.svelte';
  import Navigation from './Navigation.svelte';
  import Sidebar from './Sidebar.svelte';

  let { params = {} } = $props();
  let organization = $derived(params.organization);
  let repository = $derived(params.repository);
  let number = $derived(params.number);
  let activeTab = $derived(params.tab || 'conversation');
  let type = $derived(params.type);

  let files = $state([]);
  let loadingFiles = $state(true);
  let selectedFileIndex = $state(0);
  let selectedFile = $state(null);

  let item = $state({});
  let isPR = type == 'prs';
  let isLoading = $state(true);

  onMount(async () => {
    isLoading = true;
    item = await api.get(route(`organizations.repositories.item.show`, { organization, repository, number }));

    try {
      item.labels = JSON.parse(item.labels);
    } catch (e) {
      item.labels = [];
    }

    isLoading = false;

    if (isPR) {
      loadFiles();
    }
  });

  async function loadFiles() {
    files = await api.get(route(`organizations.repositories.item.files`, { organization, repository, number }));
    selectedFile = files[selectedFileIndex];
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
              {item.details?.head_branch} into {item.details?.base_branch}
            </span>
          </div>
        {/if}

        <Navigation bind:activeTab {organization} {repository} {type} {number} />
      {/if}

      <!-- Conversation Tab Content -->
      {#if !isPR || activeTab === 'conversation'}
        <Conversation {item} {params} />
      {/if}

      <!-- Files Changed Tab Content (PR only) -->
      {#if isPR && activeTab === 'files'}
        <FileTab {item} {files} {loadingFiles} {selectedFile} {selectedFileIndex} {params} />
      {/if}
    {/if}
  </div>
</div>

<style lang="scss">
  @import '../../../scss/components/item/item.scss';
</style>
