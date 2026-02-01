<script>
  import { onMount } from 'svelte';
  import { organization, repository } from '../stores.js';
  import { filterCommentsByReviewer, getParentComment } from '../../lib/commentUtils.js';
  import Modal from '../Modal.svelte';
  import Comment from '../Comment.svelte';

  let { isOpen = false, onClose, reviewer, allComments = [], params = {} } = $props();

  let currentIndex = $state(0);

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

  // Reset index when modal opens
  $effect(() => {
    if (isOpen) {
      currentIndex = 0;
    }
  });

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

    await api.post(route(`organizations.repositories.item.comment`, { $organization, $repository, number: params.number, comment_id: currentComment.id }), {
      resolved: currentComment.resolved
    });

    // After resolving, the filtered list will automatically update via $derived
    // If we're at the end and list shrinks, adjust index
    if (currentIndex >= reviewerComments.length && reviewerComments.length > 0) {
      currentIndex = Math.max(0, reviewerComments.length - 1);
    }
  }

  function closeModal() {
    isOpen = false;
  }
</script>

<Modal isOpen={isOpen} onClose={onClose} title="{reviewer?.user?.display_name}'s unresloved feedback:" showButtons={false}>
      {#if hasComments && currentComment}
        <div class="main-comment">
          <Comment comment={currentComment} {params} />
        </div>
      {:else}
        <div class="no-comments">
          <p>No comments from this reviewer.</p>
        </div>
      {/if}

      <!-- Navigation and Actions -->
      {#if hasComments}
        <div class="modal-controls">
          <div class="navigation-buttons">
            <button class="nav-button prev" onclick={goToPrevious} disabled={currentIndex === 0}> Previous</button>
            <button class="nav-button next" onclick={goToNext} disabled={currentIndex === totalComments - 1}>Next</button>
          </div>

          <button class="action-button" class:resolved={currentComment?.resolved} onclick={toggleResolve}>{currentComment?.resolved ? 'Unresolve' : 'Mark as Complete'}</button>
        </div>
      {/if}
</Modal>


<style lang="scss">
  @import '../../../scss/components/item/reviewer-focus-modal.scss';
</style>
