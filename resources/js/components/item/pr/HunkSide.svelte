<script>
  import { detectLanguage } from '../../../utils/syntaxHighlighter.js';
  import HighlightedDiffLine from '../../HighlightedDiffLine.svelte';
  import Comment from '../../Comment.svelte';

  let { changedLinePair = {}, selectedFile = {}, comments = [], side } = $props();

  function prefix(type) {
    if (type === 'add') return '+';
    if (type === 'del') return '-';
    return '  ';
  }

  const pair = changedLinePair[side.toLowerCase()];

</script>

<div class="side-wrapper">
  <div class="side left-side">
    <span class="line-number diff-line-{pair.type}">{pair.number}</span>
    <div class="diff-line-content diff-line-{pair.type}">
      {#if pair.type !== 'empty'}
        <span class="prefix">{prefix(pair.type)}</span>
        <HighlightedDiffLine code={pair.content} language={detectLanguage(selectedFile.filename)} />
      {/if}
    </div>
  </div>

  {#each comments as comment (comment.id)}
    {#if comment.path === selectedFile.filename && comment.line_end === pair.number && comment.side === side.toUpperCase()}
      <Comment {comment} />
    {/if}
  {/each}
</div>

<style>
  @import '../../../../scss/components/item/pr/filetab/hunk-side.scss';
</style>