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
    }

    return `${base}/#/${$organization}/${$repository}/${type}/${number}`;
  }

  function subTitle() {
    if (item.type === 'pull_request' || item.type === 'issue') {
      return `opened ${item.created_at_human} by ${item.opened_by?.display_name}`;
    }

    if (item.type === 'project') {
      return `#${item.number} updated ${item.created_at_human}`;
    }
  }

  function isCurrentUserAssigned() {
    const currentUserId = window.USER_ID;
    return item.assignees?.some(assignee => assignee.id == currentUserId);
  }
</script>

<a class="list-item" class:assigned={isCurrentUserAssigned()} href="{itemUrl(item.number)}">
  <Icon name={item.type} size="1.5rem" className="item-{item.state}" />

  <div class="content">
    <h3>{item.title}</h3>
    <div class="meta">
      {subTitle()}

      {#if item.labels?.length > 0}
        <div class="labels">
          {#each item.labels as label}
            <span class="label" style="background-color: #{label.color}4D; color: #{label.color}; border: 1px solid #{label.color};">{label.name}</span>
          {/each}
        </div>
      {/if}

      {#if item.score_breakdown?.length > 0}
        <div class="score-breakdown">
          Score: {item.importance_score}
          {#each item.score_breakdown as reason}
            <div>{reason}</div>
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
