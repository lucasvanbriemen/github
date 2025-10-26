<script>
  import { onMount } from "svelte";
  import ItemIcon from "./ItemIcon.svelte";
  let { item, itemType } = $props();

  function itemUrl(number) {
    const base = window.location.href;
    return `${base}/${number}`;
  }
</script>

<a class="list-item" data-type="{itemType}" href="{itemUrl(item.number)}">
  <ItemIcon {itemType} state={item.state} />

  <div class="content">
    <h3>{item.title}</h3>
    <div class="meta">
      opened {item.created_at_human} by <img src="{item.opened_by.avatar_url}" alt="">{item.opened_by.name}

      {#if item.labels.length > 0}
        <div class="labels">
          {#each item.labels as label}
            <span class="label" style="background-color: #{label.color}4D; color: #{label.color}; border: 1px solid #{label.color};">{label.name}</span>
          {/each}
        </div>
      {/if}
    </div>
  </div>

  <div class="assignees">
    {#if item.assignees.length > 0}
      {#each item.assignees as assignee}
        <img src="{assignee.avatar_url}" alt="">
      {/each}
    {/if}
  </div>
</a>

<style>
  .list-item {
    padding: calc(0.5rem - 4px) calc(1rem - 4px);
    background-color: var(--background-color-one);
    border-radius: 1rem;
    border: 2px solid var(--background-color-one);

    text-decoration: none;

    display: flex;
    align-items: center;
    gap: 1rem;

    .icon {
      height: 1.5rem;
      width: 1.5rem;
      margin-top: 0.25rem;
      fill: var(--success-color);

      &.closed {
        fill: var(--error-color);
      }
    }

    .content {
      display: flex;
      flex-direction: column;

      .meta, h3 {
        margin: 0.25rem;
      }

      h3 {
        font-weight: 400;
      }

      .meta {
        display: flex;
        align-items: center;
        font-size: 0.875rem;

        img {
          height: 1.25rem;
          width: 1.25rem;
          border-radius: 50%;
          margin: 0 0.25rem;
        }

        color: var(--text-color-secondary);

        .label {
          margin: 0.25rem;
          padding: 0.25rem 0.5rem;
          border-radius: 1rem;
          font-size: 0.75rem;
        }
      }
    }

    .assignees {
      margin-left: auto;
      display: flex;
      align-items: center;

      img {
        height: 2rem;
        width: 2rem;
        border-radius: 50%;
        margin-left: -1.25rem;
      }

      &:hover {
        img {
          margin-left: 0.25rem;
        }
      }
    }

    &:hover {
      cursor: pointer;
      border: 2px solid var(--primary-color-dark);
    }
  }
</style>
