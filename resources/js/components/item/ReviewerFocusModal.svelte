<script>
  import { onMount } from 'svelte';
  import { organization, repository } from '../stores.js';
  import { filterCommentsByReviewer, getParentComment } from '../../lib/commentUtils.js';
  import Modal from '../Modal.svelte';
  import Comment from '../Comment.svelte';

  let { isOpen = false, onClose, reviewer, allComments = [], params = {} } = $props();

  let currentIndex = $state(0);
  let isClosing = $state(false);

  // Reactive: filter comments by reviewer (excludes resolved)
  let reviewerComments = $derived(
    filterCommentsByReviewer(allComments, reviewer?.user?.id, false)
  );

  let currentComment = $derived.by(() => {
    const comment = reviewerComments[currentIndex];
    if (comment && comment.in_reply_to_id) {
      // Return comment with parent reference (without mutating)
      return {
        ...comment,
        parent_comment: getParentComment(comment, allComments)
      };
    }
    return comment;
  });

  let totalComments = $derived(reviewerComments.length);
  let hasComments = $derived(totalComments > 0);

  // Auto-close when all comments resolved
  $effect(() => {
    if (isOpen && hasComments === false && reviewerComments.length === 0) {
      setTimeout(() => {
        closeModal();
      }, 300);
    }
  });

  // Reset index when modal opens
  $effect(() => {
    if (isOpen) {
      currentIndex = 0;
      isClosing = false;
    }
  });

  // Keyboard shortcuts
  function handleKeydown(e) {
    if (!isOpen) return;

    if (e.key === 'Escape') {
      closeModal();
    } else if (e.key === 'ArrowLeft' || e.key === 'j') {
      e.preventDefault();
      goToPrevious();
    } else if (e.key === 'ArrowRight' || e.key === 'k') {
      e.preventDefault();
      goToNext();
    } else if (e.key === 'r') {
      e.preventDefault();
      if (currentComment) {
        toggleResolve();
      }
    }
  }

  function goToPrevious() {
    if (currentIndex > 0) {
      currentIndex--;
    }
  }

  function goToNext() {
    if (currentIndex < totalComments - 1) {
      currentIndex++;
    }
  }

  async function toggleResolve() {
    if (!currentComment) return;

    currentComment.resolved = !currentComment.resolved;

    await api.post(
      route(`organizations.repositories.item.comment`, {
        $organization,
        $repository,
        number: params.number,
        comment_id: currentComment.id
      }),
      {
        resolved: currentComment.resolved
      }
    );

    // After resolving, the filtered list will automatically update via $derived
    // If we're at the end and list shrinks, adjust index
    if (currentIndex >= reviewerComments.length && reviewerComments.length > 0) {
      currentIndex = Math.max(0, reviewerComments.length - 1);
    }
  }

  function closeModal() {
    isClosing = true;
    setTimeout(() => {
      onClose?.();
    }, 200);
  }

  function handleBackdropClick(e) {
    if (e.target === e.currentTarget) {
      closeModal();
    }
  }

  onMount(() => {
    document.addEventListener('keydown', handleKeydown);
    return () => {
      document.removeEventListener('keydown', handleKeydown);
    };
  });
</script>

<Modal isOpen={isOpen} onClose={onClose} title="{reviewer?.user?.display_name}'s unresloved feedback:" showButtons={false}>
      <!-- Comment Display Area -->
      <div class="comment-area">
        {#if hasComments && currentComment}
          <!-- Parent context if reply -->
          {#if currentComment.in_reply_to_id}
            <div class="parent-context">
              <p class="parent-label">In reply to:</p>
              {#if currentComment.parent_comment}
                <div class="parent-comment-display">
                  <img src={currentComment.parent_comment.author?.avatar_url} alt={currentComment.parent_comment.author?.name} class="parent-avatar" />
                  <div class="parent-content">
                    <strong>{currentComment.parent_comment.author?.display_name}</strong>
                    {#if currentComment.parent_comment.body}
                      <p class="parent-body">{currentComment.parent_comment.body.substring(0, 100)}...</p>
                    {/if}
                  </div>
                </div>
              {/if}
            </div>
          {/if}

          <!-- Main Comment -->
          <div class="main-comment">
            <Comment comment={currentComment} {params} />
          </div>
        {:else}
          <div class="no-comments">
            <p>No comments from this reviewer.</p>
          </div>
        {/if}
      </div>

      <!-- Navigation and Actions -->
      {#if hasComments}
        <div class="modal-controls">
          <div class="navigation-buttons">
            <button
              class="nav-button prev"
              onclick={goToPrevious}
              disabled={currentIndex === 0}
              title="Previous comment (← or j)"
            >
              Previous
            </button>
            <button
              class="nav-button next"
              onclick={goToNext}
              disabled={currentIndex === totalComments - 1}
              title="Next comment (→ or k)"
            >
              Next
            </button>
          </div>

          <button
            class="action-button"
            class:resolved={currentComment?.resolved}
            onclick={toggleResolve}
            title="Mark as complete / Unresolve (r)"
          >
            {currentComment?.resolved ? 'Unresolve' : 'Mark as Complete'}
          </button>
        </div>
      {/if}
</Modal>


<style lang="scss">
  @import '../../../scss/components/item/reviewer-focus-modal.scss';
</style>
