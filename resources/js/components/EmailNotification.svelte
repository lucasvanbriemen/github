<script>
  import { onMount } from 'svelte';

  let { params = {} } = $props();

  let id = params.id;
  let notification = $state(null);
  let loading = $state(true);
  let error = $state(null);

  onMount(async () => {
    notification = await api.get(route('notification.show', { id }));
    loading = false;
  });

  function getNotificationTypeLabel(type) {
    const labels = {
      comment_mention: 'Mentioned in comment',
      item_comment: 'Comment on item',
      item_assigned: 'Assigned to you',
      review_requested: 'Review requested',
      pr_review: 'Pull request review'
    };
    return labels[type] || type;
  }

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
  <div class="notification-detail loading">
    <p>Loading notification...</p>
  </div>
{:else}
  <div class="notification-detail">
    <!-- Header Section -->
    <div class="notification-header">
      <div class="header-top">
        <span class="type-badge">{getNotificationTypeLabel(notification.type)}</span>
        <span class="timestamp">{notification.created_at_human}</span>
      </div>
    </div>

    <!-- Triggered By Section -->
    {#if notification.triggered_by}
      <div class="triggered-by">
        <img src={notification.triggered_by.avatar_url} alt={notification.triggered_by.display_name} class="avatar" />
        <div class="user-info">
          <p class="action-text">{getActionText(notification.type, notification.triggered_by)}</p>
          <p class="username">@{notification.triggered_by.login}</p>
        </div>
      </div>
    {/if}

    <!-- Content Section - Comment Based Notifications -->
    {#if notification.type === 'comment_mention' || notification.type === 'item_comment'}
      <div class="content-section">
        <div class="item-context">
          <h3>{notification.comment?.item?.type === 'pull_request' ? 'PR' : 'Issue'} #{notification.comment?.item?.number}</h3>
          <h2>{notification.comment?.item?.title}</h2>
          <p class="repository">{notification.comment?.item?.repository?.full_name}</p>
        </div>

        <div class="comment-body">
          <h4>Comment:</h4>
          <div class="comment-text">
            {notification.comment?.body}
          </div>
        </div>
      </div>
    {/if}

    <!-- Content Section - Item Assignment/Review Request -->
    {#if notification.type === 'item_assigned' || notification.type === 'review_requested'}
      <div class="content-section">
        <div class="item-context">
          <h3>{notification.item?.type === 'pull_request' ? 'PR' : 'Issue'} #{notification.item?.number}</h3>
          <h2>{notification.item?.title}</h2>
          <p class="repository">{notification.item?.repository?.full_name}</p>
        </div>

        {#if notification.item?.body}
          <div class="item-body">
            <h4>Description:</h4>
            <div class="item-text">
              {notification.item?.body}
            </div>
          </div>
        {/if}
      </div>
    {/if}

    <!-- Content Section - PR Review -->
    {#if notification.type === 'pr_review'}
      <div class="content-section">
        <div class="review-state">
          <span class="state-badge {notification.review?.state}">
            {getReviewState(notification.review?.state)}
          </span>
        </div>

        <div class="item-context">
          <h3>PR #{notification.review?.base_comment?.item?.number}</h3>
          <h2>{notification.review?.base_comment?.item?.title}</h2>
          <p class="repository">{notification.review?.base_comment?.item?.repository?.full_name}</p>
        </div>

        {#if notification.review?.base_comment?.body}
          <div class="comment-body">
            <h4>Review Comment:</h4>
            <div class="comment-text">
              {notification.review?.base_comment?.body}
            </div>
          </div>
        {/if}
      </div>
    {/if}

    <!-- Action Buttons Section -->
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
