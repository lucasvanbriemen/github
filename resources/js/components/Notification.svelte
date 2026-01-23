<script>
  import { onMount } from 'svelte';
import Icon from './Icon.svelte';
  let { notification } = $props();

  let url = $state('');

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

    window.location.hash = url;
  }

  function completeNotification(id) {
    notification.completed = true;
    api.post(route('notifications.complete', { id }));
  }

  function setUrl() {
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
    
    url = `#/${item.repository.full_name}/${type}/${item.number}`;
  }

  onMount(() => {
    setUrl();
});
</script>

{#if !notification.completed}
  <button class="notification" onclick="{goToNotificationUrl}">
    <a href="{url}">
      <h3 class="title">{notification.subject}</h3>
      <p class="body">{getNotificationBody()}</p>
    </a>

    <Icon name="approved" size="1.5rem" onclick={() => completeNotification(notification.id)} />
  </button>
{/if}

<style>
  @import "../../scss/components/notification.scss";
</style>
