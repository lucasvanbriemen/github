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

  function getNotificationUrl(notification) {
    if (notification.type === 'comment_mention') {
      if (notification.comment.item.type === 'issue') {
        return `#/issues/${notification.comment.item.number}`;
      } else if (notification.comment.item.type === 'pull_request') {
        return `#/prs/${notification.comment.item.number}`;
      }
    }
  }

  function completeNotification(id) {
    console.log('Complete notification');
  }
</script>

<a class="notification" href="{getNotificationUrl(notification)}" >
  <div>
    <h3 class="title">{getNotificationTitle(notification)}</h3>
    <p class="body">{getNotificationBody(notification)}</p>
  </div>

  <Icon name="approved" size="1.5rem" onclick={() => completeNotification(notification.id)} />
</a>

<style>
  @import "../../scss/components/notification.scss";
</style>
