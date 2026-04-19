<script>
  import { onMount, setContext } from 'svelte';
  import FileNavigation from './FileNavigation.svelte';
  import ChangedLine from './ChangedLine.svelte';
  import ReviewPanel from './ReviewPanel.svelte';
  import { tokenizeAllLines, detectLanguage } from '../../../utils/syntaxHighlighter.js';

  let { item = {}, files = [], loadingFiles = true, selectedFileIndex = $bindable(0), selectedFile = $bindable(null), params = {}, showWhitespace = false, searchingTerm = $bindable(''), searchResults = $bindable([]) } = $props();

  const applicableExtensionsForPreview = ['svg'];

  let comments = $state([]);
  let pendingReviewComments = $state([]);
  let reviewMenuOpen = $state(false);

  let totalAdditions = $derived(files.reduce((sum, file) => sum + file.additions, 0));
  let totalDeletions = $derived(files.reduce((sum, file) => sum + file.deletions, 0));

  // Pre-computed syntax tokens for the selected file (keyed by line number per side)
  let precomputedTokens = $state({ left: new Map(), right: new Map() });
  setContext('precomputedTokens', () => precomputedTokens);

  // In-diff search: set by Ctrl+Shift+F from current selection, cleared by Esc.
  // Writes flow to the shared searchingTerm rune (module-level $state) so all
  // HighlightedDiffLine instances in the tree react to changes reliably.
  let searchTerm = $derived(searchingTerm);

  let computedSearchResults = $derived.by(() => {
    if (!searchTerm || !files?.length) return [];
    const needle = searchTerm.toLowerCase();
    const results = [];

    files.forEach((file, fileIndex) => {
      let count = 0;
      for (const hunk of (file.changes || [])) {
        for (const row of (hunk.rows || [])) {
          for (const side of ['left', 'right']) {
            const cell = row[side];
            if (!cell || cell.type === 'empty' || !cell.content) continue;
            const text = cell.content.toLowerCase();
            let idx = 0;
            while ((idx = text.indexOf(needle, idx)) !== -1) {
              count++;
              idx += needle.length;
            }
          }
        }
      }
      if (count > 0) results.push({ fileIndex, filename: file.filename, count });
    });

    return results;
  });

  $effect(() => {
    searchResults = computedSearchResults;
  });

  let totalMatches = $derived(searchResults.reduce((s, r) => s + r.count, 0));

  // Jump to a file from the search-results list and briefly flash the first match.
  // Highlighting is async (precomputed tokens rebuild on file switch), so poll
  // for the first mark to appear instead of guessing a fixed delay.
  function jumpToResult(fileIndex) {
    selectedFileIndex = fileIndex;

    const deadline = performance.now() + 2000;
    const tryFlash = () => {
      const first = document.querySelector('.pr-files mark.search-match');
      if (first) {
        first.scrollIntoView({ behavior: 'smooth', block: 'center' });
        first.classList.add('search-match-focus');
        setTimeout(() => first.classList.remove('search-match-focus'), 1500);
        return;
      }
      if (performance.now() < deadline) requestAnimationFrame(tryFlash);
    };
    requestAnimationFrame(tryFlash);
  }

  function handleSearchShortcut(e) {
    if (e.key === 'Escape' && searchTerm.term) {
      searchTerm.term = '';
      e.preventDefault();
      return;
    }

    const isSearchCombo = (e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'F' || e.key === 'f');
    if (!isSearchCombo) return;

    const selected = (window.getSelection()?.toString() || '').trim();
    if (!selected) return;

    searchTerm.term = selected;
    e.preventDefault();
  }

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

    window.addEventListener('keydown', handleSearchShortcut);
    return () => window.removeEventListener('keydown', handleSearchShortcut);
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

{#if searchTerm}
  <div class="diff-search-bar">
    <div class="header-row">
      <span class="label">Finding:</span>
      <span class="term">{searchTerm.term}</span>
      <span class="summary">{totalMatches} match{totalMatches === 1 ? '' : 'es'} in {searchResults.length} file{searchResults.length === 1 ? '' : 's'}</span>
      <button type="button" class="clear" onclick={() => (searchTerm.term = '')} title="Clear (Esc)">✕</button>
    </div>

    {#if searchResults.length > 0}
      <ul class="results">
        {#each searchResults as result}
          <li>
            <button
              type="button"
              class="result"
              class:active={result.fileIndex === selectedFileIndex}
              onclick={() => jumpToResult(result.fileIndex)}
            >
              <span class="count">{result.count}</span>
              <span class="name">{result.filename}</span>
            </button>
          </li>
        {/each}
      </ul>
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
                  <ChangedLine {changedLinePair} {selectedFile} {comments} bind:pendingReviewComments side="LEFT" {params} {showWhitespace} bind:searchingTerm />
                {/if}

                {#if selectedFile.status !== 'removed'}
                  <ChangedLine {changedLinePair} {selectedFile} {comments} bind:pendingReviewComments side="RIGHT" {params} {showWhitespace} bind:searchingTerm />
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
