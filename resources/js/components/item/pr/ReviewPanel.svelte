<script>
  import Markdown from '../../Markdown.svelte';
  import Comment from '../../Comment.svelte';

  let { params = {}, pendingReviewComments = [] } = $props();

  let reviewBody = $state('');

  let organization = params.organization;
  let repository = params.repository;
  let number = params.number;

  async function submitReview(state) {
    const comments = [];

    pendingReviewComments.forEach(comment => {
      let lineInfo = comment;
      lineInfo.line = comment.line_end;
      comments.push(lineInfo);
    });

    console.log(comments);

    return

    await api.post(
      route(`organizations.repositories.pr.review.submit`, {organization, repository, number}), {
        body: reviewBody,
        state: state,
        comments: comments,
      }
    );

    reviewBody = '';
    pendingReviewComments = [];
  }
</script>

<div class="review-panel">
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
