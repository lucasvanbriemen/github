<script>
  import { onMount } from 'svelte';
  import Markdown from '../Markdown.svelte';
  import Comment from '../Comment.svelte';

  let { item } = $props();
  let organization = $derived(params.organization || '');
  let repository = $derived(params.repository || '');
  let number = $derived(params.number || '');

  let isPR = $state(false);

  onMount(async () => {
    isPR = item.type === 'pull_request';
  });

  // Toggle functions for different comment types
  function toggleItemComment(comment) {
    comment.resolved = !comment.resolved;

    fetch(route(`organizations.repositories.item.comment`, { organization, repository, number, comment_id: comment.id }), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        resolved: comment.resolved,
      }),
    });
  }

  function toggleItemReview(review) {
    review.resolved = !review.resolved;

    fetch(route(`organizations.repositories.item.review`, { organization, repository, number, review_id: review.id }), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        resolved: review.resolved,
      }),
    });
  }

  function toggleItemReviewComment(comment) {
    comment.resolved = !comment.resolved;

    fetch(route(`organizations.repositories.item.review.comment`, { organization, repository, number, comment_id: comment.id }), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        resolved: comment.resolved,
      }),
    });
  }
</script>

<Markdown content={item.body} />

<!-- Regular Comments -->
{#each item.comments as comment}
  <Comment {comment} onToggle={toggleItemComment} />
{/each}

<!-- PR Reviews and Review Comments (PR only) -->
{#if isPR}
  {#each item.pull_request_reviews as review}
    <!-- Only render if review has a body or comments -->
    {#if (review.body !== null && review.body !== '') || (review.comments && review.comments.length > 0)}
      <div class="review-block" class:review-resolved={review.resolved}>
        <!-- Review Summary (shown if review has a body) -->
        {#if review.body !== null && review.body !== ''}
          <div class="review-header">
            <button class="item-comment-header" onclick={() => toggleItemReview(review)}>
              <img src={review.user?.avatar_url} alt={review.user?.name} />
              <span>{review.user?.display_name} {review.created_at_human}</span>
            </button>
          </div>
          <div class="review-body">
            <div class="item-comment-content">
              <Markdown content={review.body} />
            </div>
          </div>
        {/if}

        <!-- Review Line Comments with Replies -->
        <div class="review-comments">
          {#each review.comments as comment}
            <Comment
              comment={comment}
              onToggle={toggleItemReviewComment}
              onToggleReply={toggleItemReviewComment}
              indent={review.body !== null && review.body !== ''}
              showReplies={true}
            />
          {/each}
        </div>
      </div>
    {/if}
  {/each}
{/if}

<style lang="scss">
  @import '../../../scss/components/item/item.scss';
</style>
