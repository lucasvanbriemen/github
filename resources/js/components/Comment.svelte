<script>
  import Markdown from './Markdown.svelte';

  let {
    comment,
    onToggle,
    onToggleReply = onToggle,
    showReplies = false,
    indent = false
  } = $props();

  // Determine the action text based on the created_at_human format
  let actionText = comment.created_at_human?.includes('(review)')
    ? comment.created_at_human.replace(' (review)', '')
    : `commented ${comment.created_at_human}`;
</script>

<div
  class="item-comment"
  class:item-comment-resolved={comment.resolved}
  class:indent={indent}
>
  <button class="item-comment-header" onclick={() => onToggle(comment)}>
    <img src={comment.author?.avatar_url} alt={comment.author?.name} />
    <span>{comment.author?.name} {actionText}</span>
  </button>

  <div class="item-comment-body">
    <div class="item-comment-content">
      <Markdown content={comment.body} />

      {#if showReplies && comment.replies}
        <div class="item-comment-replies">
          {#each comment.replies as reply}
            <svelte:self
              comment={reply}
              onToggle={onToggleReply}
              indent={true}
            />
          {/each}
        </div>
      {/if}
    </div>
  </div>
</div>

<style>
  .item-comment {
    padding: 0.25rem 0;
    display: flex;
    flex-direction: column;

    .item-comment {
      .item-comment-content {
        border: none !important;
      }
    }
  }

  .item-comment.indent {
    margin-left: 1.5rem;
  }

  .item-comment:last-child {
    padding-bottom: 1rem;
  }

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
  }

  .item-comment-header img {
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
  }

  .item-comment-content {
    border: 2px solid var(--background-color-one);
    border-radius: 0 0 1rem 1rem;
  }

  .item-comment-body :global(.markdown-body) {
    height: auto;
  }

  .item-comment-body :global(.markdown-body p),
  .item-comment-body :global(.markdown-body li),
  .item-comment-body :global(.markdown-body strong) {
    color: var(--text-color);
  }

  .item-comment-body :global(.markdown-body) {
    border: none !important;
  }

  .item-comment-replies {
    padding: 0 0.5rem 0.5rem 0.5rem;
  }

  .item-comment-replies .item-comment {
    padding: 0.5rem 0 0 0;
  }

  .item-comment-resolved .item-comment-header {
    border-radius: 1rem;
  }

  .item-comment-resolved .item-comment-body {
    height: 0;
    overflow: hidden;
  }
</style>
