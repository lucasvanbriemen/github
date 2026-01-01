<script>
  import { onMount } from 'svelte';
  import FileNavigation from './FileNavigation.svelte';
  import ChangedLine from './ChangedLine.svelte';
  import ReviewPanel from './ReviewPanel.svelte';

  let { item = {}, files = [], loadingFiles = true, selectedFileIndex = 0, selectedFile = null, params = {}, showWhitespace = true } = $props();

  let comments = $state([]);
  let pendingReviewComments = $state([]);
  let reviewMenuOpen = $state(false);

  let totalAdditions = $derived(files.reduce((sum, file) => sum + file.additions, 0));
  let totalDeletions = $derived(files.reduce((sum, file) => sum + file.deletions, 0));

  onMount(async () => {
    const raw = item.comments.filter(c => c.type === 'review');

    raw.forEach(comment => {
      // We get the child comments for each review comment and add them to the comments array
      comment.child_comments.forEach(childComment => {
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
</script>

{#if !loadingFiles}
  <div class="pr-header">
    <FileNavigation {files} bind:selectedFileIndex bind:selectedFile bind:reviewMenuOpen />

    <div class="pr-stats-summary">
      <span class="stats-label">{files.length} {files.length === 1 ? 'file' : 'files'} changed</span>
      {#if totalAdditions > 0}
        <span class="stats-additions">+{totalAdditions}</span>
      {/if}
      {#if totalDeletions > 0}
        <span class="stats-deletions">-{totalDeletions}</span>
      {/if}
    </div>

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
      <button class="header" type="button">
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
      </button>

      <div class="file-changes">
        {#each selectedFile.changes as hunk (hunk)}
          {#each (hunk.rows || []) as changedLinePair (changedLinePair)}
            <div class="changed-line-pair">
              <ChangedLine {changedLinePair} {selectedFile} {comments} bind:pendingReviewComments side="LEFT" {params} {showWhitespace} />
              <ChangedLine {changedLinePair} {selectedFile} {comments} bind:pendingReviewComments side="RIGHT" {params} {showWhitespace} />
            </div>
          {/each}

          <div class="hunk-separator"></div>
        {/each}
      </div>
    </div>
  {/if}
</div>

<style lang="scss">
  @import '../../../../scss/components/item/pr/filetab/filetab';
</style>
