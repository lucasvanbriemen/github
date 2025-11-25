<script>
  import { onMount } from 'svelte';
  import HighlightedDiffLine from '../HighlightedDiffLine.svelte';
  import { detectLanguage } from '../../utils/syntaxHighlighter.js';
  import Comment from '../Comment.svelte';

  let { item = {}, params = {} } = $props();
  let loadingFiles = $state(true);
  let files = $state([]);
  let selectedFileIndex = $state(0);
  let selectedFile = $state(null);

  let number = $derived(item.number);
  let organization = $derived(params.organization);
  let repository = $derived(params.repository);

  function prefix(type) {
    if (type === 'add') return '+';
    if (type === 'del') return '-';
    return ' ';
  }

  async function loadFiles() {
    files = await api.get(route(`organizations.repositories.item.files`, { organization, repository, number }));
    selectedFile = files[selectedFileIndex];
    loadingFiles = false;
  }

  loadFiles();

  let comments = $state([]);


  onMount(async () => {
    // Collect inline review comments from both sources and de-duplicate by id
    comments = item.pull_request_reviews
      .map(r => r.child_comments)
      .flat();

    // Remove the diff_hunks from the comments in the files tab to avoid duplication of diffs
    comments.forEach(c => { if (c && 'diff_hunk' in c) delete c.diff_hunk; });
  });

  $effect(() => {
    selectedFile = files[selectedFileIndex];
  });
</script>

<button onclick={() => selectedFileIndex--} class="file-nav-button" class:disabled={selectedFileIndex === 0} type="button">Previous File</button>
<span class="file-nav-info">File {selectedFileIndex + 1} of {files.length}: {selectedFile?.filename}</span>
<button onclick={() => selectedFileIndex++} class="file-nav-button" class:disabled={selectedFileIndex === files.length - 1} type="button">Next File</button>

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
          {#each selectedFile.changes as hunk}
            {#each (hunk.rows || []) as changedLinePair}
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

                  {#each comments as comment}
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

                  {#each comments as comment}
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
  @import '../../../scss/components/item/filetab';
</style>
