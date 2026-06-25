<script>
  import { onMount, onDestroy } from 'svelte';
  import ItemSkeleton from './ItemSkeleton.svelte';
  import ItemHeader from './ItemHeader.svelte';
  import FileTab from './pr/FileTab.svelte';
  import Conversation from './Conversation.svelte';
  import Navigation from './Navigation.svelte';
  import Sidebar from './Sidebar.svelte';
  import Icon from '../Icon.svelte';
  import { organization, repository, repoMetadata } from '../stores';
  import CopyText from '../CopyText.svelte';
  import { querystring } from 'svelte-spa-router';
  import { get } from 'svelte/store';
  import { scrollToComment, flashHighlight } from '../../lib/highlight.js';

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
  let searchingTerm = $state('');
  let searchResults = $state([]);

  let item = $state({});
  let isPR = $state(type == 'prs');
  let isLoading = $state(true);
  let showWhitespace = $state(false);
  let unsubAbly = null;
  let itemNotifications = $state([]);

  onMount(async () => {
    isLoading = true;
    item = await api.get(route(`organizations.repositories.item.show`, { $organization, $repository, number }));

    if (!Array.isArray(item.labels)) {
      item.labels = [];
    }

    // Derive isPR from loaded data so PRs linked via issues/ URL still work
    isPR = item.type === 'pull_request';

    isLoading = false;
    loadNotifications();
    focusFromUrl();

    if (isPR) {
      loadFiles();

      const channel = `pr.${$organization}/${$repository}.${number}`;
      ably.connect([channel]);
      unsubAbly = ably.subscribe(channel, () => {
        loadFiles();
      });
    }
  });

  onDestroy(() => {
    unsubAbly?.();
    ably.disconnect();
  });

  async function loadFiles() {
    files = await api.get(route(`organizations.repositories.pr.files`, { $organization, $repository, number }));
    selectedFile = files[selectedFileIndex];
    loadingFiles = false;
  }

  async function loadNotifications() {
    itemNotifications = await api.get(route('organizations.repositories.item.notifications', { $organization, $repository, number }));
  }

  // When arriving from a notification the URL carries either a comment to
  // scroll to (?comment=123) or a request to highlight the item (?highlight=item).
  function focusFromUrl() {
    const qs = new URLSearchParams(get(querystring));

    if (qs.get('comment')) {
      scrollToComment(qs.get('comment'));
    } else if (qs.get('highlight') === 'item') {
      flashHighlight('.notification-banner');
    }
  }

  // Clicking a notification while already on the item view: scroll to its
  // comment, or flash the banner when there is no specific comment.
  function focusNotification(notification) {
    if (notification.comment_id) {
      scrollToComment(notification.comment_id);
    } else {
      flashHighlight('.notification-banner');
    }
  }

  async function completeNotification(id) {
    itemNotifications = itemNotifications.filter(n => n.id !== id);
    await api.post(route('notifications.complete', { id }));
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
  <Sidebar {item} {isPR} {isLoading} metadata={$repoMetadata} {params} {activeTab} {files} {showWhitespace} bind:selectedFileIndex bind:selectedFile bind:searchingTerm {searchResults} />

  <!-- MAIN CONTENT: Header, Body, and Comments -->
  <div class="item-main {activeTab}" class:is-pr={isPR}>
    {#if isLoading}
      <ItemSkeleton />
    {:else}

      {#if activeTab === 'conversation'}
        <CopyText text={githubUrl(item)} label="GitHub URL" />
      {/if}

      <ItemHeader {item} />

      {#if itemNotifications.length > 0}
        <div class="notification-banner">
          <div class="notification-banner-header">
            <span class="notification-banner-title">{itemNotifications.length} notification{itemNotifications.length > 1 ? 's' : ''}</span>
          </div>
          {#each itemNotifications as notification}
            <div class="notification-banner-item">
              {#if notification.triggered_by?.avatar_url}
                <img src={notification.triggered_by.avatar_url} alt="" class="notification-banner-avatar" />
              {/if}
              <button type="button" class="notification-banner-text" onclick={() => focusNotification(notification)}>{notification.subject}</button>
              <span class="notification-banner-time">{notification.created_at_human}</span>
              <button class="notification-banner-dismiss" onclick={() => completeNotification(notification.id)}>
                <Icon name="approved" size="1.2rem" />
              </button>
            </div>
          {/each}
        </div>
      {/if}

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
        <FileTab {item} {files} {loadingFiles} bind:selectedFile bind:selectedFileIndex {params} {showWhitespace} bind:searchingTerm bind:searchResults />
      {/if}
    {/if}
  </div>
</div>

<style lang="scss">
  @import '../../../scss/components/item/item.scss';
</style>
