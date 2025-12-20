<script>
  import Markdown from '../../Markdown.svelte';

  let { params = {}, pendingReviewComments = [] } = $props();

  let reviewBody = $state('');

  let organization = params.organization;
  let repository = params.repository;
  let number = params.number;

  async function submitReview(state) {
    const comments = [];

    for (const pendingComment of pendingReviewComments) {
      let lineInfo = pendingComment;
      lineInfo.line = pendingComment.line_end;
      comments.push(lineInfo);
    }

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
