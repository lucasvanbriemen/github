<script>
  import { onMount } from 'svelte';
  import Icon from './Icon.svelte';
  import ItemSkeleton from './item/ItemSkeleton.svelte';

  let { params = {} } = $props();

  let groups = $state([]);
  let orphaned = $state([]);
  let loading = $state(true);

  onMount(async () => {
    const data = await api.get(route('notifications.digest', { date: params.date }));
    groups = data.groups;
    orphaned = data.orphaned;
    loading = false;
  });

  function getItemUrl(item) {
    const type = item.type === 'pull_request' ? 'prs' : 'issues';
    return `#/${item.repository.full_name}/${type}/${item.number}`;
  }

  async function completeNotification(notification) {
    notification.completed = true;
    await api.post(route('notifications.complete', { id: notification.id }));
  }

  async function completeAll() {
    const ids = [];
    for (const group of groups) {
      for (const n of group.notifications) {
        if (!n.completed) ids.push(n.id);
      }
      for (const linked of group.linked) {
        for (const n of linked.notifications) {
          if (!n.completed) ids.push(n.id);
        }
      }
    }
    for (const n of orphaned) {
      if (!n.completed) ids.push(n.id);
    }

    await Promise.all(ids.map(id => api.post(route('notifications.complete', { id }))));
    window.location.hash = '#/';
  }
</script>

<div class="notification-overview">
  {#if loading}
    <ItemSkeleton />
  {:else}
    {#each groups as group}
      <div class="item-group">
        {#if group.item}
          <a href={getItemUrl(group.item)} class="item-header">
            <Icon name={group.item.type === 'pull_request' ? 'pull_request' : 'issue'} size="1.25rem" />
            <span class="item-title">{group.item.title}</span>
          </a>
        {/if}

        {#each group.notifications as notification}
          <div class="notification-row" class:completed={notification.completed}>
            <span class="dot {notification.type}"></span>
            <span class="notification-text">{notification.subject}</span>
            <span class="notification-time">{notification.created_at_human}</span>
            {#if !notification.completed}
              <button class="complete-btn" onclick={() => completeNotification(notification)}>
                <Icon name="approved" size="1rem" />
              </button>
            {/if}
          </div>
        {/each}

        {#each group.linked as linked}
          <div class="linked-section">
            <a href={getItemUrl(linked.item)} class="linked-header">
              <Icon name="pull_request" size="1rem" />
              <span class="linked-title">{linked.item.title}</span>
            </a>

            {#each linked.notifications as notification}
              <div class="notification-row linked" class:completed={notification.completed}>
                <span class="dot {notification.type}"></span>
                <span class="notification-text">{notification.subject}</span>
                <span class="notification-time">{notification.created_at_human}</span>
                {#if !notification.completed}
                  <button class="complete-btn" onclick={() => completeNotification(notification)}>
                    <Icon name="approved" size="1rem" />
                  </button>
                {/if}
              </div>
            {/each}
          </div>
        {/each}
      </div>
    {/each}

    {#if orphaned.length > 0}
      <div class="item-group">
        <div class="item-header orphaned-header">
          <span class="item-title">Other</span>
        </div>
        {#each orphaned as notification}
          <div class="notification-row" class:completed={notification.completed}>
            <span class="dot {notification.type}"></span>
            <span class="notification-text">{notification.subject}</span>
            <span class="notification-time">{notification.created_at_human}</span>
            {#if !notification.completed}
              <button class="complete-btn" onclick={() => completeNotification(notification)}>
                <Icon name="approved" size="1rem" />
              </button>
            {/if}
          </div>
        {/each}
      </div>
    {/if}

    <button class="button-primary" onclick={completeAll}>Complete All</button>
  {/if}
</div>

<style>
  @import "../../scss/components/notification-digest.scss";
</style>
