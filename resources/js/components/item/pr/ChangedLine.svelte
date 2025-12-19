<script>
  import { detectLanguage } from '../../../utils/syntaxHighlighter.js';
  import HighlightedDiffLine from '../../HighlightedDiffLine.svelte';
  import Comment from '../../Comment.svelte';
  import Markdown from '../../Markdown.svelte';

  let { changedLinePair = {}, selectedFile = {}, comments = [], pendingReviewComments = [], side, params } = $props();

  let organization = params.organization;
  let repository = params.repository;
  let number = params.number;

  function prefix(type) {
    if (type === 'add') return '+';
    if (type === 'del') return '-';
    return ' ';
  }

  function post_comment(e) {
    api.post(route(`organizations.repositories.item.review.comments.create`, { organization, repository, number }), {
      path: selectedFile.filename,
      line: line.number,
      side: side.toUpperCase(),
      body: line.comment,
    }).then(() => {
      line.comment = '';
      line.addingComment = false;
    });
  }

  function add_to_review() {
    const reviewComment = {
      id: Math.random(), // Temporary ID for local state
      path: selectedFile.filename,
      line_end: line.number,
      side: side.toUpperCase(),
      body: line.comment,
      author: {
        display_name: 'You',
        avatar_url: '',
      },
      created_at_human: 'pending...',
      is_pending: true,
    };
    pendingReviewComments = [...pendingReviewComments, reviewComment];
    line.comment = '';
    line.addingComment = false;
  }

  const line = changedLinePair[side.toLowerCase()];

  line.addingComment = false;
  line.comment = '';
</script>

<div class="side-wrapper" class:adding-comment={line.addingComment}>
  <div class="side left-side">
    <div class="line-number diff-line-{line.type}">
      {#if line.type !== 'empty' && prefix(line.type) !== '  '}
        <button class="add-comment-button" onclick={() => line.addingComment = !line.addingComment}>+</button>
      {/if}

      {line.number}
    </div>

    <div class="diff-line-content diff-line-{line.type}">
      {#if line.type !== 'empty'}
        <span class="prefix">{prefix(line.type)}</span>
        <HighlightedDiffLine code={line.content} language={detectLanguage(selectedFile.filename)} />
      {/if}
    </div>
  </div>

  {#each comments as comment (comment.id)}
    {#if comment.path === selectedFile.filename && comment.line_end === line.number && comment.side === side.toUpperCase()}
      <Comment {comment} {params} />
    {/if}
  {/each}

  {#each pendingReviewComments as comment (comment.id)}
    {#if comment.path === selectedFile.filename && comment.line_end === line.number && comment.side === side.toUpperCase()}
      <div class="pending-review-comment">
        <div class="comment-header">
          <span class="pending-badge">Pending</span>
          <span class="author-name">{comment.author.display_name}</span>
        </div>
        <div class="comment-body">
          <Markdown content={comment.body} canEdit={false} />
        </div>
      </div>
    {/if}
  {/each}

  {#if line.type !== 'empty' && prefix(line.type) !== '  ' && line.addingComment}
    <div class="add-comment-wrapper">
      <Markdown isEditing={true} bind:content={line.comment} />

      <div class="comment-actions">
        <button class="button-primary button-post" onclick={post_comment}>Post Comment</button>
        <button class="button-secondary button-review" onclick={add_to_review}>Start Review</button>
      </div>
    </div>
  {/if}
</div>

<style>
  @import '../../../../scss/components/item/pr/filetab/hunk-side.scss';

  .pending-review-comment {
    padding: 0.75rem;
    margin: 0.5rem 0;
    background: #f0f8ff;
    border-left: 3px solid #0969da;
    border-radius: 4px;
  }

  .comment-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
  }

  .pending-badge {
    background: #0969da;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .author-name {
    color: #666;
    font-weight: 500;
  }

  .comment-body {
    font-size: 0.95rem;
  }

  .comment-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.75rem;
  }

  .button-post {
    flex: 1;
    background-color: #0969da;

    &:hover {
      background-color: #0860ca;
    }
  }

  .button-review {
    flex: 1;
    background-color: #6e40aa;
    color: white;

    &:hover {
      background-color: #5e3099;
    }
  }
</style>