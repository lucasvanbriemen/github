<script>
  import { onMount } from 'svelte';
  import FileNavigation from './FileNavigation.svelte';
  import HunkSide from './HunkSide.svelte';

  let { item = {}, files = [], loadingFiles = true, selectedFileIndex = 0, selectedFile = null, params = {} } = $props();

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
                <HunkSide {changedLinePair} {selectedFile} {comments} side="LEFT" />
                <HunkSide {changedLinePair} {selectedFile} {comments} side="RIGHT" />
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
