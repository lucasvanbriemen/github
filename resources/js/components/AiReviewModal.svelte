<script>
  import { organization, repository } from "./stores.js";

  let { isOpen = false, onClose, item } = $props();

  let state = $state('input'); // input | analyzing | review | posting | success | error
  let userContext = $state('');
  let comments = $state([]);
  let errorMessage = $state('');
  let selectedComments = $state({});
  let editingCommentIndex = $state(null);
  let editingCommentText = $state('');

  function handleBackdropClick(e) {
    if (e.target === e.currentTarget) {
      onClose?.();
      resetModal();
    }
  }

  function resetModal() {
    state = 'input';
    userContext = '';
    comments = [];
    errorMessage = '';
    selectedComments = {};
    editingCommentIndex = null;
    editingCommentText = '';
  }

  async function startReview() {
    state = 'analyzing';
    errorMessage = '';

    try {
      const response = await api.post(
        route(`organizations.repositories.pr.ai-review.analyze`, {
          $organization,
          $repository,
          number: item.number,
        }),
        { context: userContext }
      );

      if (response.error) {
        errorMessage = response.error;
        state = 'error';
        return;
      }

      comments = response.comments || [];

      // Initialize selected state - all comments selected by default
      selectedComments = {};
      comments.forEach((_, index) => {
        selectedComments[index] = true;
      });

      state = comments.length > 0 ? 'review' : 'error';
      if (comments.length === 0) {
        errorMessage = 'No suggestions found in the PR changes.';
      }
    } catch (e) {
      errorMessage = e.message || 'Failed to analyze PR';
      state = 'error';
    }
  }

  function toggleComment(index) {
    selectedComments[index] = !selectedComments[index];
  }

  function startEditing(index) {
    editingCommentIndex = index;
    editingCommentText = comments[index].body;
  }

  function saveEdit() {
    if (editingCommentIndex !== null) {
      comments[editingCommentIndex].body = editingCommentText;
      editingCommentIndex = null;
      editingCommentText = '';
    }
  }

  function cancelEdit() {
    editingCommentIndex = null;
    editingCommentText = '';
  }

  async function postSelectedComments() {
    state = 'posting';
    errorMessage = '';

    const toPost = comments.filter((_, index) => selectedComments[index]);

    if (toPost.length === 0) {
      errorMessage = 'No comments selected';
      state = 'error';
      return;
    }

    try {
      const response = await api.post(
        route(`organizations.repositories.pr.ai-review.post-comments`, {
          $organization,
          $repository,
          number: item.number,
        }),
        { comments: toPost }
      );

      if (response.failedCount > 0) {
        errorMessage = `Posted ${response.postedCount} comments, ${response.failedCount} failed.`;
        state = 'error';
      } else {
        state = 'success';
        setTimeout(() => {
          onClose?.();
          resetModal();
        }, 1500);
      }
    } catch (e) {
      errorMessage = e.message || 'Failed to post comments';
      state = 'error';
    }
  }

  function getSelectedCount() {
    return Object.values(selectedComments).filter(Boolean).length;
  }

  function getCommentLocation(comment) {
    return `${comment.path}:${comment.line}`;
  }
</script>

{#if isOpen}
  <div class="modal-backdrop" onclick={handleBackdropClick}>
    <div class="ai-review-modal">
      {#if state === 'input'}
        <div class="modal-content">
          <h3 class="modal-title">AI Self-Review</h3>
          <p class="modal-description">GPT-4 will analyze this pull request for potential issues and improvements.</p>

          <div class="form-group">
            <label for="context">Optional Context (What should the reviewer focus on?)</label>
            <textarea
              id="context"
              bind:value={userContext}
              placeholder="e.g., Focus on error handling and edge cases..."
              class="context-input"
            />
          </div>

          <div class="modal-actions">
            <button class="button-primary-outline" onclick={onClose}>Cancel</button>
            <button class="button-primary" onclick={startReview}>Start Review</button>
          </div>
        </div>

      {:else if state === 'analyzing'}
        <div class="modal-content">
          <h3 class="modal-title">Analyzing...</h3>
          <p class="analyzing-message">GPT-4 is reviewing the PR changes...</p>
          <div class="spinner"></div>
        </div>

      {:else if state === 'review'}
        <div class="modal-content">
          <h3 class="modal-title">Suggested Comments ({getSelectedCount()} selected)</h3>
          <p class="modal-description">Review and edit the suggested comments before posting.</p>

          <div class="comments-list">
            {#each comments as comment, index (index)}
              <div class="comment-item" class:unchecked={!selectedComments[index]}>
                <div class="comment-header">
                  <input
                    type="checkbox"
                    checked={selectedComments[index]}
                    onchange={() => toggleComment(index)}
                    class="comment-checkbox"
                  />
                  <span class="comment-location">{getCommentLocation(comment)}</span>
                  {#if editingCommentIndex !== index}
                    <button
                      class="edit-btn"
                      onclick={() => startEditing(index)}
                      title="Edit comment"
                    >
                      ✏️
                    </button>
                  {/if}
                </div>

                {#if editingCommentIndex === index}
                  <textarea
                    bind:value={editingCommentText}
                    class="comment-textarea editing"
                  />
                  <div class="edit-actions">
                    <button class="button-primary-outline" onclick={cancelEdit}>Cancel</button>
                    <button class="button-primary" onclick={saveEdit}>Save</button>
                  </div>
                {:else}
                  <p class="comment-text">{comment.body}</p>
                {/if}
              </div>
            {/each}
          </div>

          <div class="modal-actions">
            <button class="button-primary-outline" onclick={onClose}>Cancel</button>
            <button
              class="button-primary"
              onclick={postSelectedComments}
              disabled={getSelectedCount() === 0}
            >
              Post {getSelectedCount()} Comment{getSelectedCount() === 1 ? '' : 's'}
            </button>
          </div>
        </div>

      {:else if state === 'posting'}
        <div class="modal-content">
          <h3 class="modal-title">Posting Comments...</h3>
          <p class="analyzing-message">Uploading comments to GitHub...</p>
          <div class="spinner"></div>
        </div>

      {:else if state === 'success'}
        <div class="modal-content">
          <h3 class="modal-title">Success!</h3>
          <p class="success-message">Comments posted successfully. The PR view will refresh.</p>
        </div>

      {:else if state === 'error'}
        <div class="modal-content">
          <h3 class="modal-title">Error</h3>
          <p class="error-message">{errorMessage}</p>

          <div class="modal-actions">
            <button class="button-primary-outline" onclick={onClose}>Close</button>
            {#if state === 'error' && comments.length > 0}
              <button class="button-primary" onclick={() => state = 'review'}>Back to Review</button>
            {:else}
              <button class="button-primary" onclick={() => state = 'input'}>Try Again</button>
            {/if}
          </div>
        </div>
      {/if}
    </div>
  </div>
{/if}

<style lang="scss">
  @import '../../scss/components/ai-review-modal';
</style>
