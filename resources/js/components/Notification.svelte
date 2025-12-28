<script>
  let { notification } = $props();
  import Icon from './Icon.svelte';

  function getNotificationTitle(notification) {
    if (notification.type === 'comment_mention') {
      return `${notification.comment.author.display_name} mentioned you in #${notification.comment.item.number}`;
    }
  }

  function getNotificationBody(notification) {
    if (notification.type === 'comment_mention') {
      return notification.comment.body;
    }
  }

  function goToNotificationUrl() {
    // If we click on the icon, we dont want to navigate to the notification
    if (notification.completed) {
      return;
    }

    if (notification.type === 'comment_mention') {
      if (notification.comment.item.type === 'issue') {
        window.location.hash = `#/issues/${notification.comment.item.number}`;
      } else if (notification.comment.item.type === 'pull_request') {
        window.location.hash = `#/prs/${notification.comment.item.number}`;
      }
    }
  }

  function completeNotification(id) {
    notification.completed = true;
    console.log('Complete notification');
  }
</script>

<button class="notification" onclick="{goToNotificationUrl}">
  <div>
    <h3 class="title">{getNotificationTitle(notification)}</h3>
    <p class="body">{getNotificationBody(notification)}</p>
  </div>

  <Icon name="approved" size="1.5rem" onclick={() => completeNotification(notification.id)} />
</button>

<style>
  @import "../../scss/components/notification.scss";
</style>
