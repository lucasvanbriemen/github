<script>
  import { detectLanguage } from '../../../utils/syntaxHighlighter.js';
  import HighlightedDiffLine from '../../HighlightedDiffLine.svelte';
  import Comment from '../../Comment.svelte';

  let { changedLinePair = {}, selectedFile = {}, comments = [] } = $props();
  
  function prefix(type) {
    if (type === 'add') return '+';
    if (type === 'del') return '-';
    return '  ';
  }
</script>

<div class="side-wrapper">
  <div class="side left-side">
    <span class="line-number diff-line-{changedLinePair.left.type}">{changedLinePair.left.number}</span>
    <div class="diff-line-content diff-line-{changedLinePair.left.type}">
      {#if changedLinePair.left.type !== 'empty'}
        <span class="prefix">{prefix(changedLinePair.left.type)}</span>
        <HighlightedDiffLine code={changedLinePair.left.content} language={detectLanguage(selectedFile.filename)} />
      {/if}
    </div>
  </div>

  {#each comments as comment (comment.id)}
    {#if comment.path === selectedFile.filename && comment.line_end === changedLinePair.left.number && comment.side === 'LEFT'}
      <Comment {comment} />
    {/if}
  {/each}
</div>