<script>
  import { onMount } from 'svelte';
  import ItemSkeleton from './item/ItemSkeleton.svelte';

  let { params = {} } = $props();

  let id = params.id;
  let notification = $state(null);
  let loading = $state(true);

  onMount(async () => {
    notification = await api.get(route('notification.show', { id }));
    loading = false;
  });

  function getActionText(type, triggeredBy) {
    if (!triggeredBy) return '';
    const name = triggeredBy.display_name || triggeredBy.login;

    const actions = {
      comment_mention: `${name} mentioned you in a comment`,
      item_comment: `${name} commented on an item`,
      item_assigned: `${name} assigned an item to you`,
      review_requested: `${name} requested your review`,
      pr_review: `${name} reviewed your pull request`
    };
    return actions[type] || '';
  }

  function getReviewState(state) {
    const states = {
      approved: 'Approved',
      commented: 'Commented',
      changes_requested: 'Requested Changes'
    };
    return states[state] || state;
  }

  async function markAsComplete() {
    await api.post(route('notifications.complete', { id }));
    window.location.hash = '#/';
  }

  function goToItem() {
    let item;
    let itemType;

    if (notification.type === 'comment_mention' || notification.type === 'item_comment') {
      item = notification.comment?.item;
      itemType = notification.comment?.item?.type === 'pull_request' ? 'prs' : 'issues';
    } else if (notification.type === 'item_assigned' || notification.type === 'review_requested') {
      item = notification.item;
      itemType = notification.item?.type === 'pull_request' ? 'prs' : 'issues';
    } else if (notification.type === 'pr_review') {
      item = notification.review?.base_comment?.item;
      itemType = notification.review?.base_comment?.item?.type === 'pull_request' ? 'prs' : 'issues';
    }

    if (item && itemType) {
      window.location.hash = `#/${item.repository.full_name}/${itemType}/${item.number}`;
    }
  }

  function textDetails() {
    let title = '';
    let body = '';

    if (notification.type === 'comment_mention' || notification.type === 'item_comment') {
      title = `${notification.comment?.author?.display_name} mentioned you in #${notification.comment?.item?.number}`;
      body = notification.comment?.body;
    }

    if (notification.type === 'item_assigned' || notification.type === 'review_requested') {
      title = `${notification.item?.title} was assigned to you`;
      body = notification.item?.body;
    }

    if (notification.type === 'pr_review') {
      title = `${notification.review?.base_comment?.author?.display_name} ${notification.review?.state} your review on #${notification.review?.base_comment?.item?.number}`;
      body = notification.review?.base_comment?.body;
    }

    return { title, body };
  }
</script>

<div class="notification-detail">
  {#if loading}
    <ItemSkeleton />
  {:else}
    <div class="triggered-by">
      <img src={notification.triggered_by.avatar_url} alt={notification.triggered_by.display_name} class="avatar" />
      <div class="user-info">
        <p class="action-text">{getActionText(notification.type, notification.triggered_by)}</p>
        <p class="username">{notification.created_at_human}</p>
      </div>
    </div>

    <div class="content-section">
      {#if notification.type === "pr_review"}
        <span class="state-badge {notification.review?.state}">
          {getReviewState(notification.review?.state)}
        </span>
      {/if}

      <h2>{textDetails().title}</h2>

      {#if textDetails().body != ""}
        <div class="content">{textDetails().body}</div>
      {/if}
    </div>

    <div class="action-buttons">
      <button class="button-primary-outline" onclick={goToItem}>
        View Item
      </button>
      <button class="button-primary" onclick={markAsComplete}>
        Mark as Complete
      </button>
    </div>
  {/if}
</div>

<style>
  @import "../../scss/components/email-notification.scss";
</style>
