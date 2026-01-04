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
    await api.post(route('notifications.complete', { id: notification.id }));
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
</script>

{#if loading}
  <div class="loading">
    <ItemSkeleton />
  </div>
{:else}
  <div class="notification-detail">
    <div class="triggered-by">
      <img src={notification.triggered_by.avatar_url} alt={notification.triggered_by.display_name} class="avatar" />
      <div class="user-info">
        <p class="action-text">{getActionText(notification.type, notification.triggered_by)}</p>
        <p class="username">{notification.created_at_human}</p>
      </div>
    </div>

    {#if notification.type === 'comment_mention' || notification.type === 'item_comment'}
      <div class="content-section">
        <div class="item-context">
          <h2>{notification.comment?.item?.title}</h2>
        </div>

        <div class="comment-body">
          <div class="comment-text">
            {notification.comment?.body}
          </div>
        </div>
      </div>
    {/if}

    {#if notification.type === 'item_assigned' || notification.type === 'review_requested'}
      <div class="content-section">
        <div class="item-context">
          <h2>{notification.item?.title}</h2>
          <p class="repository">{notification.item?.repository?.full_name}</p>
        </div>

        {#if notification.item?.body}
          <div class="item-body">
            <div class="item-text">
              {notification.item?.body}
            </div>
          </div>
        {/if}
      </div>
    {/if}

    {#if notification.type === 'pr_review'}
      <div class="content-section">
        <div class="review-state">
          <span class="state-badge {notification.review?.state}">
            {getReviewState(notification.review?.state)}
          </span>
        </div>

        <div class="item-context">
          <h2>{notification.review?.base_comment?.item?.title}</h2>
          <p class="repository">{notification.review?.base_comment?.item?.repository?.full_name}</p>
        </div>

        {#if notification.review?.base_comment?.body}
          <div class="comment-body">
            <div class="comment-text">
              {notification.review?.base_comment?.body}
            </div>
          </div>
        {/if}
      </div>
    {/if}

    <div class="action-buttons">
      <button class="button-primary-outline" onclick={goToItem}>
        View Item
      </button>
      <button class="button-primary" onclick={markAsComplete}>
        Mark as Complete
      </button>
    </div>
  </div>
{/if}

<style>
  @import "../../scss/components/email-notification.scss";
</style>
