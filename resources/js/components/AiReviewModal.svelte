<script>
  import { organization, repository } from "./stores.js";
  import Modal from "./Modal.svelte";

  let { isOpen = false, onClose, item } = $props();

  let state = $state('input'); // input | analyzing | clarifying | review | posting | success
  let userContext = $state('');
  let unclearItems = $state([]);
  let clarifications = $state({});
  let comments = $state([]);
  let selectedComments = $state({});
  let editingCommentIndex = $state(null);
  let editingCommentText = $state('');

  function resetModal() {
    state = 'input';
    userContext = '';
    unclearItems = [];
    clarifications = {};
    comments = [];
    selectedComments = {};
    editingCommentIndex = null;
    editingCommentText = '';
  }

  async function startReview() {
    state = 'analyzing';

    const response = await api.post(route(`organizations.repositories.pr.ai-review.analyze`, { $organization, $repository, number: item.number, }), { context: userContext });

    unclearItems = response.unclearItems || [];
    clarifications = {};

    state = 'clarifying';
  }

  async function submitClarifications() {
    state = 'analyzing';

    const response = await api.post(route(`organizations.repositories.pr.ai-review.generate-comments`, { $organization, $repository, number: item.number, }), {
        unclearItems: unclearItems,
        clarifications: clarifications,
      }
    );

    comments = response.comments || [];

    // Initialize selected state - all comments selected by default
    selectedComments = {};
    comments.forEach((_, index) => {
      selectedComments[index] = true;
    });

    state = 'review';
  }

  function toggleComment(index) {
    selectedComments[index] = !selectedComments[index];
  }

  function startEditing(index) {
    editingCommentIndex = index;
    editingCommentText = comments[index].body;
  }

  function saveEdit() {
    comments[editingCommentIndex].body = editingCommentText;
    editingCommentIndex = null;
    editingCommentText = '';
  }

  function cancelEdit() {
    editingCommentIndex = null;
    editingCommentText = '';
  }

  async function postSelectedComments() {
    state = 'posting';

    const toPost = comments.filter((_, index) => selectedComments[index]);
    await api.post(route(`organizations.repositories.pr.ai-review.post-comments`, { $organization, $repository, number: item.number }), { comments: toPost });
    state = 'success';

    setTimeout(() => {
      onClose?.();
      resetModal();
    }, 1500);
  }

  function getSelectedCount() {
    return Object.values(selectedComments).filter(Boolean).length;
  }

  function getCommentLocation(comment) {
    return `${comment.path}:${comment.line}`;
  }
</script>

<Modal isOpen={isOpen} onClose={() => { onClose?.(); resetModal(); }} title="AI Self-Review" showButtons={false}>
  <div class="contents">
    {#if state === 'input'}
      <p class="modal-description">GPT-4 will analyze this pull request for potential issues and improvements.</p>

      <div class="form-group">
        <label for="context">Optional Context (What should the reviewer focus on?)</label>
        <textarea id="context" bind:value={userContext} placeholder="e.g., Focus on error handling and edge cases..." class="context-input"></textarea>
      </div>

      <div class="modal-actions">
        <button class="button-primary-outline" onclick={onClose}>Cancel</button>
        <button class="button-primary" onclick={startReview}>Start Review</button>
      </div>
    {:else if state === 'analyzing'}
      <div class="spinner"></div>
    {:else if state === 'clarifying'}
      <p class="modal-description">Review the sections flagged as unclear. Clarify each one to help generate better comments.</p>

      <div class="unclear-items-list">
        {#each unclearItems as item, index (index)}
          <div class="unclear-item">
            <div class="item-header">
              <div class="item-info">
                <span class="item-location">{item.path}:{item.line}</span>
                <span class="item-reason">{item.reason}</span>
              </div>
            </div>

            <div class="code-snippet">
              <code>{item.code}</code>
            </div>

            <div class="clarification-input-group">
              <label for="clarification-{index}">Your clarification (optional):</label>
              <textarea id="clarification-{index}" bind:value={clarifications[index]} placeholder="Explain the intent or context of this code..." class="clarification-input"></textarea>
            </div>
          </div>
        {/each}

      <div class="modal-actions">
        <button class="button-primary-outline" onclick={onClose}>Cancel</button>
        <button class="button-primary" onclick={submitClarifications}>Generate Comments</button>
      </div>
    </div>

    {:else if state === 'review'}
      <p class="modal-description">Review and edit the suggested comments before posting.</p>

      <div class="comments-list">
        {#each comments as comment, index (index)}
          <div class="comment-item" class:unchecked={!selectedComments[index]}>
            <div class="comment-header">
              <input type="checkbox" checked={selectedComments[index]} onchange={() => toggleComment(index)} class="comment-checkbox"/>
              <span class="comment-location">{getCommentLocation(comment)}</span>
              {#if editingCommentIndex !== index}
                <button class="edit-btn" onclick={() => startEditing(index)} title="Edit comment">✏️</button>
              {/if}
            </div>

            {#if editingCommentIndex === index}
              <textarea bind:value={editingCommentText} class="comment-textarea editing"></textarea>
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
        <button class="button-primary" onclick={postSelectedComments} disabled={getSelectedCount() === 0}>Post {getSelectedCount()} Comment{getSelectedCount() === 1 ? '' : 's'}</button>
      </div>

    {:else if state === 'posting'}
      <div class="spinner"></div>
    {:else if state === 'success'}
      <p class="success-message">Comments posted successfully. The PR view will refresh.</p>
    {/if}
  </div>
</Modal>

<style lang="scss">
  @import '../../scss/components/ai-review-modal';
</style>
