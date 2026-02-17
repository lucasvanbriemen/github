<script>
  import { onMount, setContext } from 'svelte';
  import FileNavigation from './FileNavigation.svelte';
  import ChangedLine from './ChangedLine.svelte';
  import ReviewPanel from './ReviewPanel.svelte';
  import { tokenizeAllLines, detectLanguage } from '../../../utils/syntaxHighlighter.js';

  let { item = {}, files = [], loadingFiles = true, selectedFileIndex = $bindable(0), selectedFile = $bindable(null), params = {}, showWhitespace = false } = $props();

  const applicableExtensionsForPreview = ['svg'];

  let comments = $state([]);
  let pendingReviewComments = $state([]);
  let reviewMenuOpen = $state(false);

  let totalAdditions = $derived(files.reduce((sum, file) => sum + file.additions, 0));
  let totalDeletions = $derived(files.reduce((sum, file) => sum + file.deletions, 0));

  // Pre-computed syntax tokens for the selected file (keyed by line number per side)
  let precomputedTokens = $state({ left: new Map(), right: new Map() });
  setContext('precomputedTokens', () => precomputedTokens);

  function detectTheme() {
    return document.documentElement.getAttribute('data-theme') === 'dark' ? 'github-dark' : 'github-light';
  }

  async function precomputeHighlighting(file) {
    const language = detectLanguage(file.filename);
    const theme = detectTheme();

    const leftLines = [];
    const leftNumbers = [];
    const rightLines = [];
    const rightNumbers = [];

    for (const hunk of file.changes) {
      for (const row of (hunk.rows || [])) {
        if (row.left && row.left.type !== 'empty') {
          leftLines.push(row.left.content || '');
          leftNumbers.push(row.left.number);
        }
        if (row.right && row.right.type !== 'empty') {
          rightLines.push(row.right.content || '');
          rightNumbers.push(row.right.number);
        }
      }
    }

    const [leftTokens, rightTokens] = await Promise.all([
      tokenizeAllLines(leftLines, language, theme),
      tokenizeAllLines(rightLines, language, theme),
    ]);

    const leftMap = new Map();
    const rightMap = new Map();

    if (leftTokens) {
      leftTokens.forEach((tokens, i) => leftMap.set(leftNumbers[i], tokens));
    }
    if (rightTokens) {
      rightTokens.forEach((tokens, i) => rightMap.set(rightNumbers[i], tokens));
    }

    precomputedTokens = { left: leftMap, right: rightMap };
  }

  onMount(async () => {
    const raw = (item.comments || []).filter(c => c.type === 'review');

    raw.forEach(comment => {
      // We get the child comments for each review comment and add them to the comments array
      (comment.child_comments || []).forEach(childComment => {
        comments.push(childComment);
      });
    });

    // Create a local, non-mutating copy of comments without diff_hunk to avoid
    // altering the shared item object used by the Conversation tab.
    comments = comments.map(c => ({ ...c, diff_hunk: undefined }));

    // if we click outside the review panel, close it
    document.addEventListener('click', e => {
      if (!reviewMenuOpen) return;
      if (e.target.closest('.pr-header')) return;
      reviewMenuOpen = false;
    });
  });

  $effect(() => {
    selectedFile = files[selectedFileIndex];
  });

  $effect(() => {
    if (selectedFile && !loadingFiles) {
      precomputeHighlighting(selectedFile);
    }
  });

  function isApplicableForPreview(file) {
    if (file.status != 'added' || !applicableExtensionsForPreview.includes(file.filename.split('.').pop())) {
      return false;
    }

    return true;
  }
</script>

{#if !loadingFiles}
  <div class="pr-header">
    <FileNavigation {files} bind:selectedFileIndex bind:selectedFile bind:reviewMenuOpen {totalAdditions} {totalDeletions} />

    {#if reviewMenuOpen}
      <ReviewPanel {item} {params} bind:pendingReviewComments bind:reviewMenuOpen />
    {/if}
  </div>
{/if}

<div class="pr-files">
  {#if loadingFiles}
    <div class="loading">Loading files...</div>
  {:else}
    <div class="file">
      <div class="header">
        <span class="file-status file-status-{selectedFile.status}">{selectedFile.status}</span>
        <span class="file-name">{selectedFile.filename}</span>

        <span class="file-stats">
          {#if selectedFile.additions > 0}
            <span class="additions">+{selectedFile.additions}</span>
          {/if}
          {#if selectedFile.deletions > 0}
            <span class="deletions">-{selectedFile.deletions}</span>
          {/if}
        </span>
      </div>

      {#if !isApplicableForPreview(selectedFile) }
        <div class="file-changes">
          {#each selectedFile.changes as hunk (hunk)}
            {#each (hunk.rows || []) as changedLinePair (changedLinePair)}
              <div class="changed-line-pair">
                {#if selectedFile.status !== 'added'}
                  <ChangedLine {changedLinePair} {selectedFile} {comments} bind:pendingReviewComments side="LEFT" {params} {showWhitespace} />
                {/if}

                {#if selectedFile.status !== 'removed'}
                  <ChangedLine {changedLinePair} {selectedFile} {comments} bind:pendingReviewComments side="RIGHT" {params} {showWhitespace} />
                {/if}
              </div>
            {/each}

            <div class="hunk-separator"></div>
          {/each}
        </div>
      {:else}
        <div class="file-preview">
          {@html selectedFile.changes.map(hunk => hunk.rows.map(row => row.right?.content || '').join('\n')).join('\n')}
        </div>
      {/if}
    </div>
  {/if}
</div>

<style lang="scss">
  @import '../../../../scss/components/item/pr/filetab/filetab';
</style>
