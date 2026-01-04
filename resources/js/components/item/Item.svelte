<script>
  import { onMount, onDestroy } from 'svelte';
  import ItemSkeleton from './ItemSkeleton.svelte';
  import ItemHeader from './ItemHeader.svelte';
  import FileTab from './pr/FileTab.svelte';
  import Conversation from './Conversation.svelte';
  import Navigation from './Navigation.svelte';
  import Sidebar from './Sidebar.svelte';
  import { organization, repository } from '../stores';
  import { echo } from '../lib/echo';

  let { params = {} } = $props();
  let number = $derived(params.number);
  let activeTab = $derived(params.tab || 'conversation');
  let type = $derived(params.type);
  let metadata = $state({});

  let files = $state([]);
  let loadingFiles = $state(true);
  let selectedFileIndex = $state(0);
  let selectedFile = $state(null);

  let item = $state({});
  let isPR = type == 'prs';
  let isLoading = $state(true);
  let showWhitespace = $state(true);
  let reloadTimer = $state(null);

  async function reloadPRData() {
    const currentScrollY = window.scrollY;
    const currentFileName = selectedFile?.filename;

    isLoading = true;

    try {
      // Reload item data
      const updatedItem = await api.get(route(`organizations.repositories.item.show`, { $organization, $repository, number }));

      try {
        updatedItem.labels = JSON.parse(updatedItem.labels);
      } catch (e) {
        updatedItem.labels = [];
      }

      item = updatedItem;

      // Reload files
      if (isPR) {
        loadingFiles = true;
        files = await api.get(route(`organizations.repositories.pr.files`, { $organization, $repository, number }));

        // Restore selected file
        if (currentFileName) {
          const fileIndex = files.findIndex(f => f.filename === currentFileName);
          if (fileIndex !== -1) {
            selectedFileIndex = fileIndex;
            selectedFile = files[fileIndex];
          } else {
            selectedFile = files[0];
          }
        }
        loadingFiles = false;
      }

      // Restore scroll position
      setTimeout(() => {
        window.scrollTo(0, currentScrollY);
      }, 100);
    } catch (error) {
      console.error('Failed to reload PR data:', error);
    } finally {
      isLoading = false;
    }
  }

  onMount(async () => {
    isLoading = true;
    item = await api.get(route(`organizations.repositories.item.show`, { $organization, $repository, number }));

    try {
      item.labels = JSON.parse(item.labels);
    } catch (e) {
      item.labels = [];
    }

    isLoading = false;

    if (isPR) {
      loadFiles();
    }

    metadata = await api.get(route(`organizations.repositories.metadata`, { $organization, $repository }));

    // Listen for PR updates via WebSocket
    if (isPR && item.id) {
      echo.private(`pr.${item.id}`)
          .listen('.pr.updated', (data) => {
              console.log('PR update received:', data);

              // Clear existing timer
              if (reloadTimer) {
                clearTimeout(reloadTimer);
              }

              // Debounce: wait 2 seconds after last event before reloading
              reloadTimer = setTimeout(() => {
                reloadPRData();
                reloadTimer = null;
              }, 2000);
          });
    }
  });

  onDestroy(() => {
    if (isPR && item.id) {
      echo.leave(`pr.${item.id}`);
    }

    // Clear reload timer on unmount
    if (reloadTimer) {
      clearTimeout(reloadTimer);
    }
  });

  async function loadFiles() {
    files = await api.get(route(`organizations.repositories.pr.files`, { $organization, $repository, number }));
    selectedFile = files[selectedFileIndex];
    loadingFiles = false;
  }
</script>

<div class="item-overview">
  <Sidebar {item} {isPR} {isLoading} {metadata} {params} {activeTab} {files} bind:showWhitespace bind:selectedFile bind:selectedFileIndex />

  <!-- MAIN CONTENT: Header, Body, and Comments -->
  <div class="item-main {activeTab}" class:is-pr={isPR}>
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

        <Navigation bind:activeTab {type} {number} />
      {/if}

      <!-- Conversation Tab Content -->
      {#if !isPR || activeTab === 'conversation'}
        <Conversation {item} {params} />
      {/if}

      <!-- Files Changed Tab Content (PR only) -->
      {#if isPR && activeTab === 'files'}
        <FileTab {item} {files} {loadingFiles} bind:selectedFile bind:selectedFileIndex {params} {showWhitespace} />
      {/if}
    {/if}
  </div>
</div>

<style lang="scss">
  @import '../../../scss/components/item/item.scss';
</style>
