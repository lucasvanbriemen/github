<script>
  import { onMount } from 'svelte';
  import HighlightedDiffLine from '../HighlightedDiffLine.svelte';
  import { detectLanguage } from '../../utils/syntaxHighlighter.js';

  let { files = [], item = {}, loadingFiles = false } = $props();

  function prefix(type) {
    if (type === 'add') return '+';
    if (type === 'del') return '-';
    return ' ';
  }

  let comments = [];
  let fileLanguages = [];


  onMount(async () => {
    files.forEach((file) => {
      fileLanguages[file.filename] = detectLanguage(file.filename);
    });
  });
</script>

<div class="pr-files">
  {#if loadingFiles}
    <div class="loading">Loading files...</div>
  {:else}
    {#if !files || files.length === 0}
      <div class="diff-empty">No file changes</div>
    {:else}
      {#each files as file}
        <div class="file">
          <button class="header" type="button">
            <span class="file-status file-status-{file.status}">{file.status}</span>
            <span class="file-name">{file.filename}</span>
          </button>

          <div class="file-changes">
            {#each file.changes as hunk}
              {#each (hunk.rows || []) as changedLinePair}
                <div class="changed-line-pair">
                  <div class="side left-side">
                    <span class="line-number diff-line-{changedLinePair.left.type}">{changedLinePair.left.number}</span>
                    <div class="diff-line-content diff-line-{changedLinePair.left.type}">
                      {#if changedLinePair.left.type !== 'empty'}
                        <span class="prefix">{prefix(changedLinePair.left.type)}</span>
                        <HighlightedDiffLine code={changedLinePair.left.content} language={fileLanguages[file.filename]} />
                      {/if}
                    </div>
                  </div>

                  <div class="side right-side">
                    <span class="line-number diff-line-{changedLinePair.right.type}">{changedLinePair.right.number}</span>
                    <div class="diff-line-content diff-line-{changedLinePair.right.type}">
                      {#if changedLinePair.right.type !== 'empty'}
                        <span class="prefix">{prefix(changedLinePair.right.type)}</span>
                        <HighlightedDiffLine code={changedLinePair.right.content} language={fileLanguages[file.filename]} />
                      {/if}
                    </div>
                  </div>
                </div>
              {/each}

              <div class="hunk-separator"></div>
            {/each}
          </div>
        </div>
      {/each}
    {/if}
  {/if}
</div>

<style lang="scss">
  @import '../../../scss/components/item/filetab';
</style>
