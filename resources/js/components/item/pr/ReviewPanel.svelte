<script>
  import Markdown from '../../Markdown.svelte';

  let { item = {}, params = {}, pendingReviewComments = [] } = $props();

  let reviewBody = $state('');
  let submitting = $state(false);
  let reviewState = $state(null);

  let organization = params.organization;
  let repository = params.repository;
  let number = params.number;

  // Determine current user's review state
  $effect(() => {
    const reviews = item.comments?.filter(c => c.type === 'review') || [];
    if (reviews.length > 0) {
      const latestReview = reviews[reviews.length - 1];
      reviewState = latestReview.details?.state || null;
    }
  });

  function removePendingComment(id) {
    pendingReviewComments = pendingReviewComments.filter(c => c.id !== id);
  }

  async function submitReview(state) {
    if (submitting) return;

    submitting = true;
    try {
      // First, submit any pending review comments
      const comments = [];
      for (const pendingComment of pendingReviewComments) {
        const lineInfo = {
          path: pendingComment.path,
          line: pendingComment.line_end,
          side: pendingComment.side,
          body: pendingComment.body,
        };
        comments.push(lineInfo);
      }

      // Submit review with all pending comments
      await api.post(
        route(`organizations.repositories.pr.review.submit`, {
          organization,
          repository,
          number
        }),
        {
          body: reviewBody,
          state: state, // APPROVE, REQUEST_CHANGES, or COMMENT
          comments: comments,
        }
      );

      reviewBody = '';
      reviewState = state;
      pendingReviewComments = [];

      // Reload item to get updated review data
      const updatedItem = await api.get(
        route(`organizations.repositories.item.show`, {
          organization,
          repository,
          number
        })
      );

      item.comments = updatedItem.comments;
    } catch (error) {
      console.error('Failed to submit review:', error);
    } finally {
      submitting = false;
    }
  }

  function getReviewStateColor(state) {
    switch(state) {
      case 'APPROVE':
        return 'text-green-600';
      case 'REQUEST_CHANGES':
        return 'text-red-600';
      case 'COMMENT':
        return 'text-blue-600';
      default:
        return '';
    }
  }
</script>

<div class="review-panel">
  {#if reviewState}
    <div class="review-state {getReviewStateColor(reviewState)}">
      <strong>You {reviewState === 'APPROVE' ? 'APPROVE' : reviewState === 'REQUEST_CHANGES' ? 'requested changes' : 'commented on'} this PR</strong>
    </div>
  {/if}

  {#if pendingReviewComments.length > 0}
    <div class="pending-comments-section">
      <h4>Pending Review Comments ({pendingReviewComments.length})</h4>
      <div class="pending-comments-list">
        {#each pendingReviewComments as comment (comment.id)}
          <div class="pending-comment-item">
            <div class="comment-meta">
              <span class="file-path">{comment.path}</span>
              <span class="line-number">Line {comment.line_end}</span>
              <button class="remove-button" onclick={() => removePendingComment(comment.id)} title="Remove comment">✕</button>
            </div>
            <div class="comment-preview">
              <Markdown content={comment.body} canEdit={false} />
            </div>
          </div>
        {/each}
      </div>
    </div>
  {/if}

  <div class="review-form">
    <Markdown bind:content={reviewBody} isEditing={true} placeholder="Add a review comment..." />

    <div class="review-actions">
      <button
        class="button-primary button-comment"
        onclick={() => submitReview('COMMENT')}
        disabled={submitting}
      >
        💬 Comment
      </button>

      <button
        class="button-primary button-changes"
        onclick={() => submitReview('REQUEST_CHANGES')}
        disabled={submitting}
      >
        ⚠️ Request Changes
      </button>

      <button
        class="button-primary button-approve"
        onclick={() => submitReview('APPROVE')}
        disabled={submitting}
      >
        ✅ Approve
      </button>
    </div>
  </div>
</div>

<style lang="scss">
  .review-panel {
    margin: 1.5rem 0;
    padding: 1rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 6px;
  }

  .review-state {
    padding: 0.75rem;
    margin-bottom: 1rem;
    border-radius: 4px;
    font-size: 0.95rem;
    background: rgba(0, 0, 0, 0.05);
  }

  .pending-comments-section {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f6f8fa;
    border: 1px solid #d1d9e0;
    border-radius: 6px;

    h4 {
      margin: 0 0 0.75rem 0;
      font-size: 0.95rem;
      color: #24292f;
    }
  }

  .pending-comments-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .pending-comment-item {
    background: white;
    border: 1px solid #d1d9e0;
    border-radius: 4px;
    padding: 0.75rem;
  }

  .comment-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
    color: #666;
  }

  .file-path {
    background: #f0f0f0;
    padding: 0.2rem 0.4rem;
    border-radius: 3px;
    font-family: monospace;
    font-size: 0.8rem;
  }

  .line-number {
    color: #999;
  }

  .remove-button {
    margin-left: auto;
    background: none;
    border: none;
    color: #d1242f;
    cursor: pointer;
    font-size: 1rem;
    padding: 0;
    line-height: 1;

    &:hover {
      color: #a41e22;
    }
  }

  .comment-preview {
    font-size: 0.9rem;
  }

  .review-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .review-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;

    button {
      flex: 1;
      min-width: 120px;
      padding: 0.5rem 1rem;
      font-size: 0.9rem;
      white-space: nowrap;

      &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }
    }

    .button-comment {
      background-color: #0969da;

      &:hover:not(:disabled) {
        background-color: #0860ca;
      }
    }

    .button-changes {
      background-color: #da3633;

      &:hover:not(:disabled) {
        background-color: #ca2927;
      }
    }

    .button-approve {
      background-color: #1a7f34;

      &:hover:not(:disabled) {
        background-color: #16692d;
      }
    }
  }
</style>
