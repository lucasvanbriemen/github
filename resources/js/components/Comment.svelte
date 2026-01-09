<script>
  import { onMount } from 'svelte';
  import Markdown from './Markdown.svelte';
  import DiffHunk from './DiffHunk.svelte';
  import Self from './Comment.svelte';
  import { organization, repository } from './stores.js';

  let { comment, params = {} } = $props();

  let number = $state('');
  let isExpandedReplyForm = $state(false);
  let replyBody = $state('');

  onMount(async () => {
    number = params.number;
    console.log(comment)
  });

  function commentHeaderText() {
    if (comment.type === 'issue' || comment.type === 'code') {
      return comment.author?.display_name + ' commented  ' + comment.created_at_human;
    }

    return comment.author?.display_name + ' ' + comment.details?.state + ' the PR  ' + comment.created_at_human;
  }

  // If the body is empty and there are no child comments, we don't want to show the comment
  let showComment = $state(true);
  if ((!comment.body || comment.body.trim() === '') && (!comment.child_comments || comment.child_comments.length === 0)) {
    showComment = false;
  }

  function toggleItemComment(comment) {
    comment.resolved = !comment.resolved;

    api.post(route(`organizations.repositories.item.comment`, { $organization, $repository, number, comment_id: comment.id }), {
      resolved: comment.resolved,
    });
  }

  function expandReplyForm() {
    isExpandedReplyForm = true;
  }

  function closeReplyForm() {
    isExpandedReplyForm = false;
  }

  async function submitReply() {
    await api.post(
      route(`organizations.repositories.item.review.comments.create`, { $organization, $repository, number }),
      {
        body: replyBody,
        in_reply_to_id: comment.id
      }
    );

    replyBody = '';
    closeReplyForm();
  }
</script>

{#if showComment}
  <div class="item-comment" class:item-comment-resolved={comment.resolved}>
    <button class="item-comment-header" onclick={() => toggleItemComment(comment)}>

      {#if comment.details?.badge}
        <span class="badge">{comment.details?.badge}</span>
      {/if}

      {#if comment.author?.avatar_url}
        <img src={comment.author?.avatar_url} alt={comment.author?.name} />
      {/if}

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

        <Markdown content={comment.body} canEdit={false} />

        {#if comment.type === 'code'}
          <div class="reply-form-container" class:expanded={isExpandedReplyForm}>
            {#if !isExpandedReplyForm}
              <input type="text" onclick={expandReplyForm} placeholder="Add a reply..." class="reply-input-compact" readonly/>
            {:else}
              <Markdown bind:content={replyBody} canEdit={true} isEditing={true}/>
              <div class="reply-form-actions">
                <button class="button-primary" onclick={submitReply}>Reply</button>
                <button class="button-primary-outline" onclick={closeReplyForm}>Cancel</button>
              </div>
            {/if}
          </div>
        {/if}

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
