<script>
  import { onMount } from 'svelte';
  import FileNavigation from './FileNavigation.svelte';
  import ChangedLine from './ChangedLine.svelte';
  import ReviewPanel from './ReviewPanel.svelte';

  let { item = {}, files = [], loadingFiles = true, selectedFileIndex = 0, selectedFile = null, params = {} } = $props();

  let comments = $state([]);
  let pendingReviewComments = $state([]);

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
    <div class="file">
      <button class="header" type="button">
        <span class="file-status file-status-{selectedFile.status}">{selectedFile.status}</span>
        <span class="file-name">{selectedFile.filename}</span>
      </button>

      <div class="file-changes">
        {#each selectedFile.changes as hunk (hunk)}
          {#each (hunk.rows || []) as changedLinePair (changedLinePair)}
            <div class="changed-line-pair">
              <ChangedLine {changedLinePair} {selectedFile} {comments} {pendingReviewComments} side="LEFT" {params} />
              <ChangedLine {changedLinePair} {selectedFile} {comments} {pendingReviewComments} side="RIGHT" {params} />
            </div>
          {/each}

          <div class="hunk-separator"></div>
        {/each}
      </div>
    </div>

    <ReviewPanel {item} {params} bind:pendingReviewComments />
  {/if}
</div>

<style lang="scss">
  @import '../../../../scss/components/item/pr/filetab/filetab';
</style>
