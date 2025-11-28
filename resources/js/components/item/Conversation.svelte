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

    api.post(route(`organizations.repositories.item.comment`, { organization, repository, number, comment_id: comment.id }), {
      resolved: comment.resolved,
    });
  }

  function toggleItemReview(review) {
    review.resolved = !review.resolved;

    api.post(route(`organizations.repositories.item.review`, { organization, repository, number, review_id: review.id }), {
      resolved: review.resolved,
    });
  }

  function toggleItemReviewComment(comment) {
    comment.resolved = !comment.resolved;

    api.post(route(`organizations.repositories.item.review.comment`, { organization, repository, number, comment_id: comment.id }), {
      resolved: comment.resolved,
    });
  }

  function mergePR() {
    api.post(route(`organizations.repositories.pr.merge`, { organization, repository, number }));
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
    {#if review.body}
      <Comment comment={review} onToggle={toggleItemReview} onToggleReply={toggleItemReviewComment} />
    {:else}
      <!-- We If you have a standlone PR comment, it will have no review content, so we render that sepertly -->
      {#each review.child_comments as comment}
        <Comment {comment} onToggle={toggleItemReviewComment} />
      {/each}
    {/if}
  {/each}

  <button class="button-primary" onclick={() => mergePR()} >Merge</button>
{/if}
