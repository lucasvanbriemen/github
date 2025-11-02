<script>
  import { onMount } from 'svelte';
  import Sidebar from './Sidebar.svelte';
  import Markdown from './Markdown.svelte';
  import Comment from './Comment.svelte';

  let { params = {} } = $props();
  let organization = $derived(params.organization || '');
  let repository = $derived(params.repository || '');
  let number = $derived(params.number || '');

  let item = $state({});
  let isPR = $state(false);

  onMount(async () => {
    const res = await fetch(route(`organizations.repositories.item.show`, { organization, repository, number }));
    item = await res.json();

    try {
      item.labels = JSON.parse(item.labels);
    } catch (e) {
      item.labels = [];
    }

    isPR = item.type === 'pull_request';
  });

  // Generate label style with proper color formatting
  function getLabelStyle(label) {
    return `background-color: #${label.color}4D; color: #${label.color}; border: 1px solid #${label.color};`;
  }

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

<div class="item-overview">
  <!-- SIDEBAR: Assignees, Labels, and Reviewers -->
  <Sidebar {params} selectedDropdownSection="Issues">
    <!-- Assignees Section -->
    <div class="group">
      <span class="group-title">Assignees</span>
      {#each item.assignees as assignee}
        <div class="assignee">
          <img src={assignee.avatar_url} alt={assignee.name} />
          <span>{assignee.name}</span>
        </div>
      {/each}
    </div>

    <!-- Labels Section -->
    <div class="group">
      <span class="group-title">Labels</span>
      <div class="labels">
        {#each item.labels as label}
          <span class="label" style={getLabelStyle(label)}>
            {label.name}
          </span>
        {/each}
      </div>
    </div>

    <!-- Reviewers Section (PR only) -->
    {#if isPR}
      <div class="group">
        <span class="group-title">Reviewers</span>
        {#each item.requested_reviewers as reviewer}
          <div class="reviewer">
            <img src={reviewer.user.avatar_url} alt={reviewer.user.name} />
            <span>{reviewer.user.name}</span>
            <span>{reviewer.state}</span>
          </div>
        {/each}
      </div>
    {/if}
  </Sidebar>

  <!-- MAIN CONTENT: Header, Body, and Comments -->
  <div class="item-main">
    <!-- Item Header: Title and Metadata -->
    <div class="item-header">
      <h2>{item.title}</h2>
      <div>
        created {item.created_at_human} by
        <img src={item.opened_by?.avatar_url} alt={item.opened_by?.name} />
        {item.opened_by?.name}
        <span class="item-state item-state-{item.state}">{item.state}</span>
      </div>
    </div>

    <!-- PR Header: Branch Information (PR only) -->
    {#if isPR}
      <div class="item-header-pr">
        <span class="item-header-pr-title">
          <img src={item.opened_by?.avatar_url} alt={item.opened_by?.name} />
          {item.opened_by?.name} wants to merge
          {item.details.head_branch} into {item.details.base_branch}
        </span>
      </div>
    {/if}

    <!-- Item Body: Main Description -->
    <div class="item-body">
      <Markdown content={item.body} />
    </div>

    <!-- Regular Comments -->
    {#each item.comments as comment}
      <Comment {comment} onToggle={toggleItemComment} />
    {/each}

    <!-- PR Reviews and Review Comments (PR only) -->
    {#if isPR}
      {#each item.pull_request_reviews as review}
        <!-- Only render if review has a body or comments -->
        {#if (review.body !== null && review.body !== '') || (review.comments && review.comments.length > 0)}
          <!-- Review Summary (shown if review has a body) -->
          <div class="item-comment" class:item-comment-resolved={review.resolved}>
            {#if review.body !== null && review.body !== ''}
              <Comment
                comment={{
                  ...review,
                  author: review.user,
                  created_at_human: review.created_at_human + ' (review)'
                }}
                onToggle={toggleItemReview}
              />
            {/if}

            <!-- Review Line Comments with Replies -->
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
        {/if}
      {/each}
    {/if}
  </div>
</div>

<style>

  .group {
    border: 1px solid var(--border-color);
    background-color: var(--background-color);
    border-radius: 0.5rem;
    width: calc(95% - 1rem);
    margin: 1rem auto -0.5rem auto;
    padding: 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;

    .group-title {
      font-size: 0.75rem;
      color: var(--text-color-secondary);
    }

    .assignee, .reviewer {
      display: flex;
      align-items: center;
      gap: 0.5rem;

      img {
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 50%;
      }
    }

    .labels {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;

        .label {
          margin: 0.25rem 0;
          padding: 0.25rem 0.5rem;
          border-radius: 1rem;
          font-size: 0.75rem;
        }
    }
  }

  .edit-button {
    margin-top: 1rem;
    margin-left: 2.5%;
  }

  .item-overview {
    height: 100%;
    width: 100%;
    display: flex;
    gap: 1rem;
    overflow: auto;

    .item-main {
      width: calc(85vw - 3rem);
      display: flex;
      flex-direction: column;
      gap: 1rem;
      margin-top: 1rem;

      .item-header {
        background-color: var(--background-color-one);
        padding: 1rem;
        border-radius: 0.5rem;

        h2 {
          margin: 0;
        }

        .item-state {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          color: white;
          text-transform: capitalize;
          
          width: fit-content;
          padding: 0.25rem;
          background-color: var(--success-color);
          border-radius: 0.5rem;
          
          &.item-state-closed {
            background-color: var(--error-color);
          }
        }

        div {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          margin-top: 0.5rem;
          color: var(--text-color-secondary);
        }

        img {
          width: 1rem;
          height: 1rem;
          border-radius: 50%;
        }
      }

      .item-header-pr-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-color-secondary);
        margin: 0.5rem 0;

        img {
          width: 1rem;
          height: 1rem;
          border-radius: 50%;
        }
      }

    }
  }
</style>
