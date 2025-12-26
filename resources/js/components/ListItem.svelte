<script>
  import Icon from "./Icon.svelte";
  let { item } = $props();

  function itemUrl(number) {
    const base = window.location.href;
    return `${base}/${number}`;
  }
</script>

<a class="list-item" href="{itemUrl(item.number)}">
  <Icon name={item.type} size="1.5rem" className="item-{item.state}" />

  <div class="content">
    <h3>{item.title}</h3>
    <div class="meta">
      opened {item.created_at_human} by <img src="{item.opened_by?.avatar_url}" alt="">{item.opened_by?.display_name}

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
