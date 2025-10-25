<script>
  import { onMount } from "svelte";
  let { item, itemType } = $props();

  onMount(() => {
    console.log(item);
  });
</script>

<article class="list-item">
  <div class="state-icon">
    {#if itemType === 'issue'}
      <svg color="open.fg" viewBox="0 0 16 16" width="16" height="16" fill="currentColor" display="inline-block" overflow="visible" style="vertical-align:text-bottom" class="icon {item.state}">
        <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
        <path d="M8 0a8 8 0 1 1 0 16A8 8 0 0 1 8 0M1.5 8a6.5 6.5 0 1 0 13 0 6.5 6.5 0 0 0-13 0"/>
      </svg>
    {:else if itemType === 'pull_request'}
    {/if}
  </div>

  <div class="content">
    <h3>{item.title}</h3>
    <div class="meta">{item.created_at_human} by <img src="{item.opened_by.avatar_url}" alt="">{item.opened_by.name} </div>
  </div>
</article>

<style>
  .list-item {
    padding: 0.5rem 1rem;
    background-color: var(--background-color-one);
    border-radius: 1rem;

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
      }
    }
  }
</style>
