<script>
  import Icon from "./Icon.svelte";
  let { item } = $props();

  function itemUrl(number) {
    const base = window.location.href;
    return `${base}/${number}`;
  }

  function subTitle() {
    if (item.type === 'pr' || item.type === 'issue') {
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
