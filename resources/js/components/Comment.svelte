<script>
  import Markdown from './Markdown.svelte';
  import DiffHunk from './DiffHunk.svelte';
  import Self from './Comment.svelte';

  let { comment, onToggle, onToggleReply = onToggle, showReplies = false, indent = false } = $props();

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
    <span>{comment.author?.display_name} {actionText}</span>
  </button>

  <div class="item-comment-body">
    <div class="item-comment-content">
      <DiffHunk
        diffHunk={comment.diff_hunk}
        path={comment.path}
        startLine={comment.line_start}
        endLine={comment.line_end}
      />

      <Markdown content={comment.body} />

      {#if comment.child_comments}
        <div class="item-comment-replies">
          {#each comment.child_comments as reply}
            <Self comment={reply} onToggle={onToggleReply} indent={true} />
          {/each}
        </div>
      {/if}
    </div>
  </div>
</div>

<style lang="scss">
  @import '../../scss/components/comment';
</style>
