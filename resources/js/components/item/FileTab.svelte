<script>
  let { files = [], loadingFiles = false } = $props();

  function prefix(type) {
    if (type === 'add') return '+';
    if (type === 'del') return '-';
    return ' ';
  }
</script>

<div class="pr-files">
  {#if loadingFiles}
    <div class="loading">Loading files...</div>
  {:else}
    {#if !files || files.length === 0}
      <div class="diff-empty">No file changes</div>
    {:else}
      {#each files as file}
        <div class="diff-file">
          <button class="diff-file-header" type="button">
            <div class="diff-file-header-left">
              <span class="diff-file-status diff-file-status-{file.status}">{file.status}</span>
              <span class="diff-file-name">{file.filename}</span>
            </div>
            <div class="diff-file-stats">
              <span class="diff-stats-additions">+{file.additions}</span>
              <span class="diff-stats-deletions">-{file.deletions}</span>
            </div>
          </button>

          <div class="diff-table-container">
            <div class="diff-table diff-table-side-by-side">
              {#each file.changes as hunk}
                {#each (hunk.rows || []) as row}
                  <div class="diff-row" style="display:flex;">
                    <div class="diff-line-number {row.left.type === 'add' ? 'diff-line-add' : row.left.type === 'del' ? 'diff-line-del' : ''} {row.left.type === 'empty' ? 'diff-line-number-empty' : ''}" style="text-align:right;">
                      {row.left.num ?? ''}
                    </div>
                    <div class="diff-line-content {row.left.type === 'add' ? 'diff-line-add' : row.left.type === 'del' ? 'diff-line-del' : row.left.type === 'empty' ? 'diff-line-empty' : ''}" style="flex:1;">
                      {#if row.left.type !== 'empty'}
                        <span class="diff-line-prefix">{prefix(row.left.type)}</span>
                        <span class="diff-line-code">{row.left.content}</span>
                      {/if}
                    </div>
                    <div class="diff-line-number {row.right.type === 'add' ? 'diff-line-add' : row.right.type === 'del' ? 'diff-line-del' : ''} {row.right.type === 'empty' ? 'diff-line-number-empty' : ''}" style="text-align:right;">
                      {row.right.num ?? ''}
                    </div>
                    <div class="diff-line-content {row.right.type === 'add' ? 'diff-line-add' : row.right.type === 'del' ? 'diff-line-del' : row.right.type === 'empty' ? 'diff-line-empty' : ''}" style="flex:1;">
                      {#if row.right.type !== 'empty'}
                        <span class="diff-line-prefix">{prefix(row.right.type)}</span>
                        <span class="diff-line-code">{row.right.content}</span>
                      {/if}
                    </div>
                  </div>
                {/each}
              {/each}
            </div>
          </div>
        </div>
      {/each}
    {/if}
  {/if}
</div>

<style lang="scss">
  @import '../../../scss/components/item/item.scss';
  /* Ensure div-based rows align like table cells */
  .diff-table-side-by-side .diff-row > .diff-line-number { min-width: 50px; width: 50px; }
  .diff-table-side-by-side .diff-row > .diff-line-content { min-width: 0; }
</style>
