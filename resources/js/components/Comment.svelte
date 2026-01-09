<script>
  import { onMount } from 'svelte';
  import Markdown from './Markdown.svelte';
  import DiffHunk from './DiffHunk.svelte';
  import Self from './Comment.svelte';

  let { comment, params = {} } = $props();

  let organization = $state('');
  let repository = $state('');
  let number = $state('');
  let showReplyForm = $state(false);
  let replyBody = $state('');
  let isSubmittingReply = $state(false);

  onMount(async () => {
    organization = params.organization;
    repository = params.repository;
    number = params.number;
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

  // Toggle functions for different comment types
  function toggleItemComment(comment) {
    comment.resolved = !comment.resolved;

    api.post(route(`organizations.repositories.item.comment`, { organization, repository, number, comment_id: comment.id }), {
      resolved: comment.resolved,
    });
  }

  // Reply to comment functions
  function toggleReplyForm() {
    showReplyForm = !showReplyForm;
    if (!showReplyForm) {
      replyBody = '';
    }
  }

  async function submitReply() {
    if (!replyBody.trim()) return;

    isSubmittingReply = true;
    try {
      // Check if this is a code comment (has path field) or a regular comment
      const isCodeComment = !!comment.path;

      const payload = {
        body: replyBody,
        in_reply_to_id: comment.id,
      };

      if (isCodeComment) {
        // For code comments, include the diff context
        payload.path = comment.path;
        payload.line = comment.line_start || comment.line_end;
        payload.side = comment.side || 'RIGHT';
      }

      const response = await api.post(
        route(`organizations.repositories.item.review.comments.create`, {
          organization,
          repository,
          number
        }),
        payload
      );

      if (response.success || response.id) {
        // Clear form and close it
        replyBody = '';
        showReplyForm = false;

        // Refresh the item to show the new reply
        // For now, we'll just show a success message
        console.log('Reply posted successfully');
      }
    } catch (error) {
      console.error('Error posting reply:', error);
    } finally {
      isSubmittingReply = false;
    }
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

        <!-- Reply button and form -->
        <div class="item-comment-actions">
          <button class="reply-button" onclick={toggleReplyForm}>
            {showReplyForm ? 'Cancel' : 'Reply'}
          </button>
        </div>

        {#if showReplyForm}
          <div class="reply-form">
            <textarea
              placeholder="Add a reply..."
              bind:value={replyBody}
              class="reply-textarea"
            ></textarea>
            <div class="reply-form-actions">
              <button
                class="reply-submit-button"
                onclick={submitReply}
                disabled={isSubmittingReply || !replyBody.trim()}
              >
                {isSubmittingReply ? 'Posting...' : 'Reply'}
              </button>
              <button
                class="reply-cancel-button"
                onclick={toggleReplyForm}
                disabled={isSubmittingReply}
              >
                Cancel
              </button>
            </div>
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
