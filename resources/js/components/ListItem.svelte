<script>
  import Icon from "./Icon.svelte";
  import { organization, repository } from "./stores.js";

  let { item } = $props();

  function itemUrl(number) {
    const base = window.location.origin;

    let type = 'issues';
    if (item.type === 'pull_request') {
      type = 'prs';
    } else if (item.type === 'project') {
      type = 'projects';
    } else if (item.type === 'release') {
      type = 'releases';
    }

    // Use item data if stores are not set (e.g., on homepage)
    const org = $organization || item.repository?.full_name?.split('/')[0];
    const repo = $repository || item.repository?.full_name?.split('/')[1];

    return `${base}/#/${org}/${repo}/${type}/${number}`;
  }

  function subTitle() {
    if (item.type === 'pull_request' || item.type === 'issue') {
      return `opened ${item.created_at_human} by ${item.opened_by?.display_name}`;
    }

    if (item.type === 'project') {
      return `#${item.number} updated ${item.created_at_human}`;
    }

    if (item.type === 'release') {
      return `created ${item.created_at_human} ago by ${item.author?.display_name}`;
    }
  }

  function isCurrentUserAssigned() {
    const currentUserId = window.USER_ID;
    return item.assignees?.some(assignee => assignee.id == currentUserId);
  }
</script>

<a class="list-item {item.type}-{item.state}" class:assigned={isCurrentUserAssigned()} class:has-notifications={item.notification_count > 0} href="{itemUrl(item.number)}">
  <div class="icon-wrapper">
    <Icon name={item.type} size="1.5rem" className="item-{item.state}" />
    {#if item.notification_count > 0}
      <span class="notification-badge">{item.notification_count}</span>
    {/if}
  </div>

  <div class="content">
    <h3>{item.title}</h3>
    <div class="meta">
      {subTitle()}

      {#if item.labels?.length > 0}
        <span class="devider"></span>

        <div class="labels">
          {#each item.labels as label}
            <span class="label" style="background-color: #{label.color}4D; color: #{label.color}; border: 1px solid #{label.color};">{label.name}</span>
          {/each}
        </div>
      {/if}
    </div>
  </div>

  <div class="assignees">
    {#if item.assignees?.length > 0}
      {#each item.assignees as assignee}
        <img src="{assignee.avatar_url}" alt="">
      {/each}
    {/if}
  </div>
</a>

<style lang="scss">
  @import '../../scss/components/list-item.scss';
</style>
