<script>
  import { onMount } from 'svelte';
  import Markdown from '../Markdown.svelte';
  import Comment from '../Comment.svelte';

  let { item, params = {} } = $props();
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

{#each item.comments as comment}
  <Comment {comment} onToggle={toggleItemComment} />
{/each}

{#if isPR}
  {#each item.pull_request_reviews as review}
  {#if review.body}
    <Comment comment={review} onToggle={toggleItemReview} onToggleReply={toggleItemReviewComment} />
  {:else}
    <!-- We If you have a standlone PR comment, it will have no review content, so we render that sepertly -->
    {#each review.child_comments as comment}
      <Comment {comment} onToggle={toggleItemReviewComment} />
    {/each}
    {/if}
  {/each}
{/if}
