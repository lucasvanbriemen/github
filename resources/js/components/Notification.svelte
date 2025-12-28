<script>
  let { notification } = $props();
  import Icon from './Icon.svelte';

  function getNotificationTitle() {
    if (notification.type === 'comment_mention') {
      return `${notification.comment.author.display_name} mentioned you in #${notification.comment.item.number}`;
    }

    if (notification.type === 'item_comment') {
      return `${notification.comment.author.display_name} commented on #${notification.comment.item.number}`;
    }

    return 'Notification';
  }

  function getNotificationBody() {
    if (notification.type === 'comment_mention' || notification.type === 'item_comment') {
      return notification.comment.body;
    }

    return notification.type;
  }

  function goToNotificationUrl() {
    // If we click on the icon, we dont want to navigate to the notification
    if (notification.completed) {
      return;
    }

    if (notification.type === 'comment_mention' || notification.type === 'item_comment') {
      if (notification.comment.item.type === 'issue') {
        window.location.hash = `#/${notification.comment.item.repository.full_name}/issues/${notification.comment.item.number}`;
      } else if (notification.comment.item.type === 'pull_request') {
        window.location.hash = `#/${notification.comment.item.repository.full_name}/prs/${notification.comment.item.number}`;
      }
    }
  }

  function completeNotification(id) {
    notification.completed = true;
    api.post(route('notifications.complete', { id }));
  }
</script>

<button class="notification" onclick="{goToNotificationUrl}">
  <div>
    <h3 class="title">{getNotificationTitle()}</h3>
    <p class="body">{getNotificationBody()}</p>
  </div>

  <Icon name="approved" size="1.5rem" onclick={() => completeNotification(notification.id)} />
</button>

<style>
  @import "../../scss/components/notification.scss";
</style>
