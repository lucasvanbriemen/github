<script>
  import { detectLanguage } from '../../../utils/syntaxHighlighter.js';
  import HighlightedDiffLine from '../../HighlightedDiffLine.svelte';
  import Comment from '../../Comment.svelte';
  import Markdown from '../../Markdown.svelte';

  let { changedLinePair = {}, selectedFile = {}, comments = [], side } = $props();

  function prefix(type) {
    if (type === 'add') return '+';
    if (type === 'del') return '-';
    return '  ';
  }

  const line = changedLinePair[side.toLowerCase()];

  line.addingComment = false;

</script>

<div class="side-wrapper" class:addingComment={line.addingComment}>
  <div class="side left-side">
    <div class="line-number diff-line-{line.type}">
      {#if line.type !== 'empty' && prefix(line.type) !== '  '}
        <span class="add-comment-button" onclick={() => line.addingComment = !line.addingComment}>+</span>
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
    <Markdown />
  {/if}
</div>

<style>
  @import '../../../../scss/components/item/pr/filetab/hunk-side.scss';
</style>