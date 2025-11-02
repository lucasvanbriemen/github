<script>
  import { onMount } from 'svelte';
  import Sidebar from './Sidebar.svelte';
  import Markdown from './Markdown.svelte';

  let { params = {} } = $props();
  let organization = $derived(params.organization || '');
  let repository = $derived(params.repository || '');
  let number = $derived(params.number || '');

  let item = $state({});
  let isPR = $state(false);

  onMount(async () => {
    const res = await fetch(route(`organizations.repositories.item.show`, { organization, repository, number }));
    item = await res.json();

    console.log('DEBUG: Loaded item:', {
      type: item.type,
      isPR: item.type === 'pull_request',
      commentsCount: item.comments?.length || 0,
      reviewsCount: item.pull_request_reviews?.length || 0
    });

    if (item.pull_request_reviews) {
      item.pull_request_reviews.forEach((review, idx) => {
        console.log(`DEBUG: Review ${idx}:`, {
          id: review.id,
          hasBody: review.body !== null && review.body !== '',
          bodyLength: review.body?.length || 0,
          commentsCount: review.comments?.length || 0
        });
      });
    }

    try {
      item.labels = JSON.parse(item.labels);
    } catch (e) {
      item.labels = [];
    }

    isPR = item.type === 'pull_request';
  });

  function toggleResolved(comment) {
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
</script>

<div class="item-overview">
  <Sidebar {params} selectedDropdownSection="Issues">
    <div class="group">
      <span class="group-title">Assignees</span>
      {#each item.assignees as assignee}
        <div class="assignee">
          <img src={assignee.avatar_url} alt={assignee.name} />
          <span>{assignee.name}</span>
        </div>
      {/each}
    </div>

    <div class="group">
      <span class="group-title">Labels</span>
      <div class="labels">
        {#each item.labels as label}
          <span class="label" style="background-color: #{label.color}4D; color: #{label.color}; border: 1px solid #{label.color};">{label.name}</span>
        {/each}
      </div>
    </div>

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

  <div class="item-main">
    <div class="item-header">
      <h2>{item.title}</h2>
      <div>
        created {item.created_at_human} by <img src={item.opened_by?.avatar_url} alt={item.opened_by?.name} /> {item.opened_by?.name}
        <span class="item-state item-state-{item.state}">{item.state}</span>
      </div>
    </div>

    {#if isPR}
      <div class="item-header-pr">
        <span class="item-header-pr-title"><img src={item.opened_by?.avatar_url} alt={item.opened_by?.name} /> {item.opened_by?.name} wants to merge {item.details.head_branch} into {item.details.base_branch}</span>
      </div>
    {/if}

    <div class="item-body">
      <Markdown content={item.body} />
    </div>

    {#each item.comments as comment}
      <div class="item-comment" class:item-comment-resolved={comment.resolved}>
        <button class="item-comment-header" onclick={() => toggleResolved(comment)}>
          <img src={comment.author?.avatar_url} alt={comment.author?.name} />
          <span>{comment.author?.name} commented {comment.created_at_human}</span>
        </button>
        <div class="item-comment-body">
          <Markdown content={comment.body} />
        </div>
      </div>
    {/each}

    {#if isPR}
      {#each item.pull_request_reviews as review, reviewIndex}
        <!-- Review summary (if body exists) -->
        {#if review.body !== null && review.body !== ''}
          <div class="item-comment" class:item-comment-resolved={review.resolved}>
            <button class="item-comment-header" onclick={() => {
              console.log(`DEBUG: Review ${reviewIndex} header clicked:`, {
                id: review.id,
                user: review.user?.name,
                hasBody: true,
                commentsCount: review.comments?.length || 0
              });
              toggleResolved(review);
            }}>
              <img src={review.user.avatar_url} alt={review.user.name} />
              <span>{review.user.name} reviewed {review.created_at_human}</span>
            </button>
            <div class="item-comment-body">
              <Markdown content={review.body} />
            </div>
          </div>
        {/if}

        <!-- Review line comments (always render, independent of review body) -->
        {#each review.comments as comment, commentIndex}
          <div class="item-comment" class:item-comment-resolved={comment.resolved} class:part-of-review={review.body !== null && review.body !== ''}>
            <button class="item-comment-header" onclick={() => {
              toggleResolved(comment);
            }}>
              <img src={comment.author?.avatar_url} alt={comment.author?.name} />
              <span>{comment.author?.name} commented {comment.created_at_human}</span>
            </button>
            <div class="item-comment-body">
              <Markdown content={comment.body} />

              {#each comment.replies as reply, replyIndex}
                <div class="item-comment" class:item-comment-resolved={reply.resolved}>
                  <button class="item-comment-header" onclick={() => {
                    toggleResolved(reply);
                  }}>
                    <img src={reply.author?.avatar_url} alt={reply.author?.name} />
                    <span>{reply.author?.name} replied {reply.created_at_human}</span>
                  </button>
                  <div class="item-comment-body">
                    <Markdown content={reply.body} />
                  </div>
                </div>
              {/each}
            </div>
          </div>
        {/each}
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

      .part-of-review {
        margin-left: 1rem;
      }


      .item-comment {
        padding: 0.25rem 0;
        display: flex;
        flex-direction: column;

        &:last-child {
          padding-bottom: 1rem;
        }

        .item-comment {
          margin-left: 1rem;
        }

        .item-comment-header {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          color: var(--text-color-secondary);
          background-color: var(--background-color-one);
          padding: 1rem;
          border-radius: 1rem 1rem 0 0;
          border: none;
          cursor: pointer;
          font-size: 14px;

          img {
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
          }
        }

        .item-comment-body {
          :global(.markdown-body) {
            border: 2px solid var(--background-color-one);
            border-radius: 0 0 1rem 1rem;
            height: auto;

            /* Most comments dont have a hiarchy, so we dont need to style it */
            :global(p), :global(li), :global(strong) {
              color: var(--text-color);
            }
          }
        }
        
        &.item-comment-resolved {
          .item-comment-header {
            border-radius: 1rem;
          }

          .item-comment-body {
            height: 0;
            overflow: hidden;
          }
        }
      }
    }
  }
</style>
