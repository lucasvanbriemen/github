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

  function completeNotification(id) {
    console.log('Complete notification');
  }
</script>

<div class="notification">
  <div>
    <h3 class="title">{getNotificationTitle(notification)}</h3>
    <p class="body">{getNotificationBody(notification)}</p>
  </div>

  <Icon name="approved" size="1.5rem" onclick={() => completeNotification(notification.id)} />
</div>

<style>
  @import "../../scss/components/notification.scss";
</style>
