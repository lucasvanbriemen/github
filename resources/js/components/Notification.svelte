<script>
  let { notification } = $props();
  import Icon from './Icon.svelte';

  function getNotificationTitle() {
    if (notification.type === 'comment_mention') {
      return `${notification.comment.author.display_name} mentioned you in #${notification.comment.item.number}`;
    }

    if (notification.type === 'assigned_to_item') {
      const itemType = notification.item.type === 'issue' ? 'issue' : 'PR';
      const actorName = notification.actor?.display_name || 'Someone';
      return `${actorName} assigned you to ${itemType} #${notification.item.number}`;
    }

    if (notification.type === 'activity_on_assigned_item') {
      const activityMap = {
        comment: 'commented on',
        review: 'reviewed',
        review_comment: 'commented on',
      };
      const activity = activityMap[notification.metadata?.activity_type] || 'updated';
      const actorName = notification.actor?.display_name || 'Someone';
      const itemType = notification.item.type === 'issue' ? 'issue' : 'PR';
      return `${actorName} ${activity} ${itemType} #${notification.item.number}`;
    }

    if (notification.type === 'workflow_failed') {
      const workflowName = notification.metadata?.workflow_name || 'Workflow';
      const prNumber = notification.metadata?.pr_number;
      return `${workflowName} failed on PR #${prNumber}`;
    }
  }

  function getNotificationBody() {
    if (notification.type === 'comment_mention') {
      return notification.comment.body;
    }

    if (notification.type === 'assigned_to_item' || notification.type === 'activity_on_assigned_item') {
      return notification.item.title;
    }

    if (notification.type === 'workflow_failed') {
      return notification.metadata?.pr_title || '';
    }
  }

  function goToNotificationUrl() {
    // If we click on the icon, we dont want to navigate to the notification
    if (notification.completed) {
      return;
    }

    if (notification.type === 'comment_mention') {
      if (notification.comment.item.type === 'issue') {
        window.location.hash = `#/${notification.comment.item.repository.full_name}/issues/${notification.comment.item.number}`;
      } else if (notification.comment.item.type === 'pull_request') {
        window.location.hash = `#/${notification.comment.item.repository.full_name}/prs/${notification.comment.item.number}`;
      }
    }

    if (notification.type === 'assigned_to_item' || notification.type === 'activity_on_assigned_item') {
      const item = notification.item;
      const path = item.type === 'issue' ? 'issues' : 'prs';
      const repoFullName = item.repository?.full_name;
      if (repoFullName) {
        window.location.hash = `#/${repoFullName}/${path}/${item.number}`;
      }
    }

    if (notification.type === 'workflow_failed') {
      const prNumber = notification.metadata?.pr_number;
      const repoFullName = notification.repository?.full_name;
      if (prNumber && repoFullName) {
        window.location.hash = `#/${repoFullName}/prs/${prNumber}`;
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
