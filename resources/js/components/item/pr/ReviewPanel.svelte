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

  async function submitReview(state) {
    if (submitting) return;

    console.log(pendingReviewComments);
    return;

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
</script>

<div class="review-panel">
  {#if reviewState}
    <strong>Finish your review</strong>
  {/if}

  {#if pendingReviewComments.length > 0}
    <div class="pending-comments-section">
      <h4>Pending Review Comments</h4>
      <div class="pending-comments-list">
        {#each pendingReviewComments as comment (comment.id)}
          <div class="pending-comment-item">
            <div class="comment-meta">
              <span class="file-path">{comment.path}</span>
              <span class="line-number">Line {comment.line_end}</span>
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
        Comment
      </button>

      <button
        class="button-primary button-changes"
        onclick={() => submitReview('REQUEST_CHANGES')}
        disabled={submitting}
      >
        Request Changes
      </button>

      <button
        class="button-primary button-approve"
        onclick={() => submitReview('APPROVE')}
        disabled={submitting}
      >
        Approve
      </button>
    </div>
  </div>
</div>

<style lang="scss">
  .review-panel {
    position: absolute;
    right: 1rem;
    top: 1.25rem;

    margin: 1.5rem 0;
    padding: 1rem;
    background: var(--background-color-two);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    width: 50%;
    z-index: 10;

    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .review-state {
    padding: 0.75rem;
    margin-bottom: 1rem;
    border-radius: 4px;
    font-size: 0.95rem;
  }

  .pending-comments-section {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: var(--background-color-one);
    border: 1px solid var(--border-color);
    border-radius: 6px;

    h4 {
      margin: 0 0 0.75rem 0;
      font-size: 0.95rem;
    }
  }

  .pending-comments-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .pending-comment-item {
    background: var(--background-color);
    border: 1px solid var(--border-color);
    border-radius: 4px;
    padding: 0.75rem;
  }

  .comment-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
  }

  .file-path {
    background: var(--background-color-two);
    padding: 0.2rem 0.4rem;
    border-radius: 3px;
    font-family: monospace;
    font-size: 0.8rem;
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
      background-color: var(--primary-color);

      &:hover:not(:disabled) {
        background-color: var(--primary-color-dark);
      }
    }

    .button-changes {
      background-color: var(--error-color);

      &:hover:not(:disabled) {
        background-color: #b13930;
      }
    }

    .button-approve {
      background-color: var(--success-color);

      &:hover:not(:disabled) {
        background-color: #318340;
      }
    }
  }
</style>
