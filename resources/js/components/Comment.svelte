<script>
  import { onMount } from 'svelte';
  import Markdown from './Markdown.svelte';
  import DiffHunk from './DiffHunk.svelte';
  import Self from './Comment.svelte';

  let { comment, params = {} } = $props();

  let organization = $state('');
  let repository = $state('');
  let number = $state('');

  onMount(async () => {
    organization = params.organization;
    repository = params.repository;
    number = params.number;
  });

  function commentHeaderText() {
    if (comment.type === 'issue' || comment.type === 'code') {
      return comment.author.display_name + ' commented  ' + comment.created_at_human;
    }

    return comment.author.display_name + ' ' + comment.details?.state + ' the PR  ' + comment.created_at_human;
  }

  // If the body is empty and there are no child comments, we don't want to show the comment
  let showComment = $state(true);
  if ((!comment.body || comment.body.trim() === '') && (!comment.child_comments || comment.child_comments.length === 0)) {
    showComment = false;
  }

  // Toggle functions for different comment types
  function toggleItemComment(comment) {
    comment.resolved = !comment.resolved;

    api.post(route(`organizations.repositories.item.comment`, { organization, repository, number, comment_id: comment.id }), {
      resolved: comment.resolved,
    });
  }

</script>

{#if showComment}
  <div class="item-comment" class:item-comment-resolved={comment.resolved}>
    <button class="item-comment-header" onclick={() => toggleItemComment(comment)}>
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

        {#if comment.child_comments}
          <div class="item-comment-replies">
            {#each comment.child_comments as comment}
              <Self {comment} {params} />
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
