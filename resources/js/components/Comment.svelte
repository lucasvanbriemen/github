<script>
  import Markdown from './Markdown.svelte';
  import DiffHunk from './DiffHunk.svelte';
  import Self from './Comment.svelte';

  let { comment, onToggle, onToggleReply = onToggle} = $props();

  function commentHeaderText() {
    if (comment.type === 'issue' || comment.type === 'code') {
      return comment.author.display_name + ' commented  ' + comment.created_at_human;
    }

    return comment.author.display_name + ' ' + comment.details?.state + ' the PR  ' + comment.created_at_human;
  }

  // If the body is empty and there are no child comments, we don't want to show the comment
  let showComment = $state(true);
  if ((!comment.body || comment.body.trim() === '') && (!comment.details?.child_comments || comment.details.child_comments.length === 0)) {
    showComment = false;
  }

</script>

{#if showComment}
  <div class="item-comment" class:item-comment-resolved={comment.resolved}>
    <button class="item-comment-header" onclick={() => onToggle(comment)}>
      <img src={comment.author?.avatar_url} alt={comment.author?.name} />
      <span>{commentHeaderText()}</span>
    </button>

    <div class="item-comment-body">
      <div class="item-comment-content">
        <!-- Reply to a comment, means the top comment aleady has a diff hunk -->
        {#if comment.diff_hunk && !comment.in_reply_to_id}
          <DiffHunk
            diffHunk={comment.diff_hunk}
            path={comment.path}
            startLine={comment.line_start}
            endLine={comment.line_end}
          />
        {/if}

        <Markdown content={comment.body} />

        {#if comment.details?.child_comments}
          <div class="item-comment-replies">
            {#each comment.details.child_comments as comment}
              <Self {comment} onToggle={onToggleReply} />
            {/each}
          </div>
        {/if}
      </div>
    </div>
  </div>
{/if}

<style lang="scss">
  @import '../../scss/components/comment';
</style>
