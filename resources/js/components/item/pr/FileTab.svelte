<script>
  import { onMount } from 'svelte';
  import HighlightedDiffLine from '../../HighlightedDiffLine.svelte';
  import { detectLanguage } from '../../../utils/syntaxHighlighter.js';
  import Comment from '../../Comment.svelte';
  import FileNavigation from './FileNavigation.svelte';
  import Markdown from '../../Markdown.svelte';

  let { item = {}, files = [], loadingFiles = true, selectedFileIndex = 0, selectedFile = null, params = {} } = $props();

  function prefix(type) {
    if (type === 'add') return '+';
    if (type === 'del') return '-';
    return '  ';
  }

  let comments = $state([]);

  onMount(async () => {
    const raw = item.comments.filter(c => c.type === 'review');

    raw.forEach(comment => {
      // We get the child comments for each review comment and add them to the comments array
      comment.child_comments.forEach(childComment => {
        comments.push(childComment);
        console.log(childComment);
      });
    });

    // Create a local, non-mutating copy of comments without diff_hunk to avoid
    // altering the shared item object used by the Conversation tab.
    comments = comments.map(c => ({ ...c, diff_hunk: undefined }));
  });

  $effect(() => {
    selectedFile = files[selectedFileIndex];
  });
</script>

{#if !loadingFiles}
  <FileNavigation {files} bind:selectedFileIndex bind:selectedFile />
{/if}

<div class="pr-files">
  {#if loadingFiles}
    <div class="loading">Loading files...</div>
  {:else}
    {#if !files || files.length === 0}
      <div class="diff-empty">No file changes</div>
    {:else}
      <div class="file">
        <button class="header" type="button">
          <span class="file-status file-status-{selectedFile.status}">{selectedFile.status}</span>
          <span class="file-name">{selectedFile.filename}</span>
        </button>

        <div class="file-changes">
          {#each selectedFile.changes as hunk (hunk)}
            {#each (hunk.rows || []) as changedLinePair (changedLinePair)}
              <div class="changed-line-pair">
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

                <div class="side-wrapper">
                  <div class="side right-side">
                    <span class="line-number diff-line-{changedLinePair.right.type}">{changedLinePair.right.number}</span>
                    <div class="diff-line-content diff-line-{changedLinePair.right.type}">
                      {#if changedLinePair.right.type !== 'empty'}
                        <span class="prefix">{prefix(changedLinePair.right.type)}</span>
                        <HighlightedDiffLine code={changedLinePair.right.content} language={detectLanguage(selectedFile.filename)} />
                      {/if}
                    </div>
                  </div>

                  {#each comments as comment (comment.id)}
                    {#if comment.path === selectedFile.filename && comment.line_end === changedLinePair.right.number && comment.side === 'RIGHT'}
                      <Comment {comment} />
                    {/if}
                  {/each}
                </div>
              </div>
            {/each}

            <div class="hunk-separator"></div>
          {/each}
        </div>
      </div>
    {/if}
  {/if}
</div>

<style lang="scss">
  @import '../../../../scss/components/item/pr/filetab/filetab';
</style>
