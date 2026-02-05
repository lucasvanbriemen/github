<script>
  import Markdown from '../../Markdown.svelte';
  import Comment from '../../Comment.svelte';
  import { organization, repository } from '../../stores';

  let { params = {}, pendingReviewComments = [], reviewMenuOpen = $bindable(false), item = {} } = $props();

  let reviewBody = $state('');

  let number = params.number;

  async function submitReview(state) {
    const comments = [];

    pendingReviewComments.forEach(comment => {
      let lineInfo = comment;

      lineInfo.line = comment.line_end;

      // Unset some properties that are not needed for the API
      delete lineInfo.id;
      delete lineInfo.line_end;
      delete lineInfo.author;
      delete lineInfo.details;
      delete lineInfo.created_at_human;

      comments.push(lineInfo);
    });

    await api.post(
      route(`organizations.repositories.pr.review.submit`, {$organization, $repository, number}), {
        body: reviewBody,
        state: state,
        comments: comments,
      }
    );

    reviewBody = '';
    pendingReviewComments = [];
    reviewMenuOpen = false;
  }

  function handleBackdropClick() {
    reviewMenuOpen = false;
  }
</script>

<!-- Mobile backdrop overlay -->
{#if reviewMenuOpen}
  <div class="review-panel-backdrop" onclick={handleBackdropClick}></div>
{/if}

<div class="review-panel">
  <button class="close-button" type="button" onclick={handleBackdropClick} aria-label="Close review panel">✕</button>

  {#each pendingReviewComments as comment (comment.id)}
    <Comment {comment} {params} />
  {/each}

  <Markdown bind:content={reviewBody} isEditing={true} />

  <div class="review-actions">
    <button class="button-primary button-comment" onclick={() => submitReview('COMMENT')}>Comment</button>
    <button class="button-primary button-changes" onclick={() => submitReview('REQUEST_CHANGES')}>Request Changes</button>
    <button class="button-primary button-approve" onclick={() => submitReview('APPROVE')}>Approve</button>
  </div>
</div>

<style lang="scss">
  @import '../../../../scss/components/item/pr/filetab/submit-review';
</style>
