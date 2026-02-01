<script>
  import { onMount } from 'svelte';
  import ItemSkeleton from './ItemSkeleton.svelte';
  import ItemHeader from './ItemHeader.svelte';
  import FileTab from './pr/FileTab.svelte';
  import Conversation from './Conversation.svelte';
  import Navigation from './Navigation.svelte';
  import Sidebar from './Sidebar.svelte';
  import { organization, repository, repoMetadata } from '../stores';
  import CopyText from '../CopyText.svelte';

  let { params = {} } = $props();
  let number = $derived(params.number);
  let activeTab = $derived(params.tab || 'conversation');
  let type = $derived(params.type);
  organization.set(params.organization);
  repository.set(params.repository);

  let files = $state([]);
  let loadingFiles = $state(true);
  let selectedFileIndex = $state(0);
  let selectedFile = $state(null);

  let item = $state({});
  let isPR = type == 'prs';
  let isLoading = $state(true);
  let showWhitespace = $state(false);

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
  });

  async function loadFiles() {
    files = await api.get(route(`organizations.repositories.pr.files`, { $organization, $repository, number }));
    selectedFile = files[selectedFileIndex];
    loadingFiles = false;
  }

  function githubUrl(item) {
    let urltype;
    if (isPR) {
      urltype = 'pull';
    } else {
      urltype = 'issues';
    }

    return `https://github.com/${$organization}/${$repository}/${urltype}/${item.number}?stay=1`;
  }
</script>

<div class="item-overview">
  <Sidebar {item} {isPR} {isLoading} metadata={$repoMetadata} {params} {activeTab} {files} {showWhitespace} {selectedFileIndex} {selectedFile} />

  <!-- MAIN CONTENT: Header, Body, and Comments -->
  <div class="item-main {activeTab}" class:is-pr={isPR}>
    {#if isLoading}
      <ItemSkeleton />
    {:else}

      {#if activeTab === 'conversation'}
        <CopyText text={githubUrl(item)} label="GitHub URL" />
      {/if}

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
