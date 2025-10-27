<script>
  import { onMount } from 'svelte';
  import Sidebar from './Sidebar.svelte';
  import Markdown from './Markdown.svelte';

  let { params = {} } = $props();
  let organization = $derived(params.organization || '');
  let repository = $derived(params.repository || '');
  let number = $derived(params.number || '');

  let item = $state({});

  onMount(async () => {
    const res = await fetch(route(`organizations.repositories.item.show`, { organization, repository, number }));
    item = await res.json();
  });

  function toggleResolved(comment) {
    comment.resolved = !comment.resolved;
  }
</script>

<div class="item-overview">
  <Sidebar {params} selectedSection="Issues" />

  <div class="item-main">
    <div class="item-header">
      <h2>{item.title}</h2>
      <div>
        created {item.created_at_human} by <img src={item.opened_by?.avatar_url} alt={item.opened_by?.name} /> {item.opened_by?.name}
        <span class="item-state item-state-{item.state}">{item.state}</span>
      </div>
    </div>

    <div class="item-body">
      <Markdown content={item.body} />
    </div>

    {#each item.comments as comment}
      <div class="item-comment" class:item-comment-resolved={comment.resolved}>
        <button class="item-comment-header" on:click={() => toggleResolved(comment)}>
          <img src={comment.author?.avatar_url} alt={comment.author?.name} />
          <span>{comment.author?.name} commented {comment.created_at_human}</span>
        </button>
        <div class="item-comment-body">
          <Markdown content={comment.body} />
        </div>
      </div>
    {/each}
  </div>
</div>

<style>
  .item-overview {
    height: 100%;
    width: 100%;
    display: flex;
    gap: 1rem;
    overflow: auto;

    .item-main {
      width: calc(85vw - 3rem);
      display: flex;
      flex-direction: column;
      gap: 1rem;
      margin-top: 1rem;

      .item-header {
        background-color: var(--background-color-one);
        padding: 1rem;
        border-radius: 0.5rem;

        h2 {
          margin: 0;
        }

        .item-state {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          color: white;
          text-transform: capitalize;
          
          width: fit-content;
          padding: 0.25rem;
          background-color: var(--success-color);
          border-radius: 0.5rem;
          
          &.item-state-closed {
            background-color: var(--error-color);
          }
        }

        div {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          margin-top: 0.5rem;
          color: var(--text-color-secondary);
        }

        img {
          width: 1rem;
          height: 1rem;
          border-radius: 50%;
        }
      }

      .item-comment {
        padding: 0.25rem 0;
        display: flex;
        flex-direction: column;
        .item-comment-header {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          color: var(--text-color-secondary);
          background-color: var(--background-color-one);
          padding: 1rem;
          border-radius: 1rem 1rem 0 0;
          border: none;
          cursor: pointer;
          font-size: 14px;

          img {
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
          }
        }

        .item-comment-body {
          :global(.markdown-body) {
            border: 2px solid var(--background-color-one);
            border-radius: 0 0 1rem 1rem;
            height: auto;

            /* Most comments dont have a hiarchy, so we dont need to style it */
            :global(p), :global(li), :global(strong) {
              color: var(--text-color);
            }
          }
        }
        
        &.item-comment-resolved {
          .item-comment-header {
            border-radius: 1rem;
          }

          .item-comment-body {
            height: 0;
            overflow: hidden;
          }
        }
      }
    }
  }
</style>
