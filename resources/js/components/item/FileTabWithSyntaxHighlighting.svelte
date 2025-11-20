<script>
  import HighlightedDiffLine from '../HighlightedDiffLine.svelte';
  import { detectLanguage } from '../../utils/syntaxHighlighter.js';

  let { files = [], loadingFiles = false } = $props();

  function prefix(type) {
    if (type === 'add') return '+';
    if (type === 'del') return '-';
    return ' ';
  }

  // Store detected languages for each file
  let fileLanguages = $derived(
    files.reduce((acc, file) => {
      acc[file.filename] = detectLanguage(file.filename);
      return acc;
    }, {})
  );
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
            <span class="language-badge">{fileLanguages[file.filename]}</span>
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
                        <HighlightedDiffLine
                          code={changedLinePair.left.content}
                          language={fileLanguages[file.filename]}
                        />
                      {/if}
                    </div>
                  </div>

                  <div class="side right-side">
                    <span class="line-number diff-line-{changedLinePair.right.type}">{changedLinePair.right.number}</span>
                    <div class="diff-line-content diff-line-{changedLinePair.right.type}">
                      {#if changedLinePair.right.type !== 'empty'}
                        <span class="prefix">{prefix(changedLinePair.right.type)}</span>
                        <HighlightedDiffLine
                          code={changedLinePair.right.content}
                          language={fileLanguages[file.filename]}
                        />
                      {/if}
                    </div>
                  </div>
                </div>
              {/each}
            {/each}
          </div>
        </div>
      {/each}
    {/if}
  {/if}
</div>

<style lang="scss">
  @import '../../../scss/components/item/filetab';

  .language-badge {
    margin-left: auto;
    padding: 2px 8px;
    background: #f0f0f0;
    border-radius: 4px;
    font-size: 11px;
    text-transform: uppercase;
    font-weight: 600;
    color: #666;
  }
</style>
