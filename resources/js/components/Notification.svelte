<script>
  let { notification } = $props();
</script>

{#if notification.type === 'comment_mention'}
  <div class="notification comment-mention">
    <h3>Comment Mention</h3>
    <p>You were mentioned in a comment: "{notification.comment.body}"</p>
  </div>
{:else}
  <div class="notification generic">
    <h3>Notification</h3>
    <p>{notification.message}</p>
  </div>
{/if}

<style>
  @import "../../scss/components/dashboard.scss";
</style>
