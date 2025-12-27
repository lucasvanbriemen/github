<script>
  let { notification } = $props();

  function getNotificationTitle(notification) {
    if (notification.type === 'comment_mention') {
      return 'Ilya mentioned you in #' + notification.comment.item.number;
    }
  }

  function getNotificationBody(notification) {
    if (notification.type === 'comment_mention') {
      return notification.comment.body;
    }
  }
</script>

<div class="notification">
  <h3 class="title">{getNotificationTitle(notification)}</h3>
  <p class="body">{getNotificationBody(notification)}</p>
</div>

<style>
  @import "../../scss/components/dashboard.scss";
</style>
