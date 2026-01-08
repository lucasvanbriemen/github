<script>
  let { notification } = $props();
  import Icon from './Icon.svelte';

  const stateMap = {
    approved: 'approved',
    changes_requested: 'requested changes',
    commented: 'commented',
  };

  function getNotificationTitle() {
    if (notification.type === 'comment_mention') {
      return `${notification.comment.author.display_name} mentioned you in #${notification.comment.item.number}`;
    }

    if (notification.type === 'item_comment') {
      return `${notification.comment.author.display_name} commented on #${notification.comment.item.number}`;
    }

    if (notification.type === 'item_assigned') {
      return `#${notification.item.number} was assigned to you`;
    }

    if (notification.type === 'review_requested') {
      return `You were requested to review #${notification.item.number}`;
    }

    if (notification.type === 'pr_review') {
      return `${notification.review.base_comment.author.display_name} ${stateMap[notification.review.state]} on #${notification.review.base_comment.item.number}`;
    }

    return 'Notification';
  }

  function getNotificationBody() {
    if (notification.type === 'comment_mention' || notification.type === 'item_comment') {
      return notification.comment.body;
    }

    if (notification.type === 'item_assigned' || notification.type === 'review_requested') {
      return notification.item.title;
    }

    if (notification.type === 'pr_review') {
      return notification.review.base_comment.body;
    }

    return notification.type;
  }

  function goToNotificationUrl() {
    // If we click on the icon, we dont want to navigate to the notification
    if (notification.completed) {
      return;
    }

    let item = {};
    let type = '';

    if (notification.type === 'comment_mention' || notification.type === 'item_comment') {
      item = notification.comment.item;
    }

    if (notification.type === 'item_assigned' || notification.type === 'review_requested') {
      item = notification.item;
    }

    if (notification.type === 'pr_review') {
      item = notification.review.base_comment.item;
    }

    if (item.type === 'issue') {
      type = 'issues';
    } else if (item.type === 'pull_request') {
      type = 'prs';
    }
    
    window.location.hash = `#/${item.repository.full_name}/${type}/${item.number}`;
  }

  function completeNotification(id) {
    notification.completed = true;
    api.post(route('notifications.complete', { id }));
  }
</script>

{#if !notification.completed}
  <button class="notification" onclick="{goToNotificationUrl}">
    <div>
      <h3 class="title">{getNotificationTitle()}</h3>
      <p class="body">{getNotificationBody()}</p>
    </div>

    <Icon name="approved" size="1.5rem" onclick={() => completeNotification(notification.id)} />
  </button>
{/if}

<style>
  @import "../../scss/components/notification.scss";
</style>
