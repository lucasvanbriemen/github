<script>
  import { detectLanguage } from '../../../utils/syntaxHighlighter.js';
  import HighlightedDiffLine from '../../HighlightedDiffLine.svelte';
  import Comment from '../../Comment.svelte';
  import Markdown from '../../Markdown.svelte';

  let { changedLinePair = {}, selectedFile = {}, comments = [], pendingReviewComments = $bindable([]), side, params, showWhitespace = true } = $props();

  let organization = params.organization;
  let repository = params.repository;
  let number = params.number;
  let sideWrapperElement;

  let mergedComments = [...comments, ...pendingReviewComments];

  function prefix(type) {
    if (type === 'add') return '+';
    if (type === 'del') return '-';
    return '  ';
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
    if (line.comment == "") return;

    const reviewComment = {
      id: Math.random(),
      path: selectedFile.filename,
      line_end: line.number,
      side: side.toUpperCase(),
      body: line.comment,
      author: {
        display_name: 'You',
        avatar_url: '',
      },
      details: {
        badge: 'pending',
        state: 'commented',
      },
      created_at_human: ''
    };
    pendingReviewComments = [...pendingReviewComments, reviewComment];
    line.comment = '';
    line.addingComment = false;
  }

  let line = changedLinePair[side.toLowerCase()];

  // If hiding whitespace and this is a whitespace-only change, treat as normal line and hide segments
  let displayLine = $derived(
    !showWhitespace && line.whitespace_only
      ? { ...line, type: 'normal', segments: null }
      : line
  );

  line.addingComment = false;
  line.comment = '';
</script>

<div class="side-wrapper" class:adding-comment={line.addingComment} bind:this={sideWrapperElement}>
  <div class="side left-side">
    <div class="line-number diff-line-{displayLine.type}">
      {#if displayLine.type !== 'empty' && prefix(displayLine.type) !== '  '}
        <button class="add-comment-button" onclick={() => line.addingComment = !line.addingComment}>+</button>
      {/if}

      {displayLine.number}
    </div>

    <div class="diff-line-content diff-line-{displayLine.type}">
      {#if displayLine.type !== 'empty'}
        <span class="prefix">{prefix(displayLine.type)}</span>
        <HighlightedDiffLine
          code={displayLine.content}
          language={detectLanguage(selectedFile.filename)}
          segments={displayLine.segments}
          lineType={displayLine.type}
          {showWhitespace}
        />
      {/if}
    </div>
  </div>

  {#each mergedComments as comment (comment.id)}
    {#if comment.path === selectedFile.filename && comment.line_end === line.number && comment.side === side.toUpperCase()}
      <Comment {comment} {params} />
    {/if}
  {/each}

  {#each pendingReviewComments as comment (comment.id)}
    {#if comment.path === selectedFile.filename && comment.line_end === line.number && comment.side === side.toUpperCase()}
      <Comment {comment} {params} />
    {/if}
  {/each}

  {#if line.type !== 'empty' && prefix(line.type) !== '  ' && line.addingComment}
    <div class="add-comment-wrapper">
      <Markdown isEditing={true} bind:content={line.comment} />

      <div class="comment-actions">
        <button class="button-primary-outline button-review" onclick={add_to_review}>Start Review</button>
        <button class="button-primary button-post" onclick={post_comment}>Post Comment</button>
      </div>
    </div>
  {/if}
</div>

<style>
  @import '../../../../scss/components/item/pr/filetab/hunk-side.scss';
</style>