<script>
  import { detectLanguage } from '../../../utils/syntaxHighlighter.js';
  import HighlightedDiffLine from '../../HighlightedDiffLine.svelte';
  import Comment from '../../Comment.svelte';
  import Markdown from '../../Markdown.svelte';

  let { changedLinePair = {}, selectedFile = {}, comments = [], side, params } = $props();

  let organization = params.organization;
  let repository = params.repository;
  let number = params.number;

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

  const line = changedLinePair[side.toLowerCase()];

  line.addingComment = false;
  line.comment = '';
</script>

<div class="side-wrapper" class:addingComment={line.addingComment}>
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
      <Comment {comment} />
    {/if}
  {/each}

  {#if line.type !== 'empty' && prefix(line.type) !== '  ' && line.addingComment}
    <div class="add-comment-wrapper">  
      <Markdown isEditing={true} bind:content={line.comment} />

      <button class="button-primary" onclick={post_comment}>Post Comment</button>
    </div>
  {/if}
</div>

<style>
  @import '../../../../scss/components/item/pr/filetab/hunk-side.scss';
</style>