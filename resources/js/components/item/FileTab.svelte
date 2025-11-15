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
        <div class="file">
          <button class="header" type="button">
            <span class="file-status file-status-{file.status}">{file.status}</span>
            <span class="file-name">{file.filename}</span>
          </button>

          <div class="file-changes">
            {#each file.changes as hunk}
              {#each (hunk.rows || []) as row}
                <div class="diff-row">
                  <span class="diff-line-number diff-line-{row.left.type}">{row.left.num ?? ''}</span>
                  <div class="diff-line-content diff-line-{row.left.type}">
                    {#if row.left.type !== 'empty'}
                      <span class="diff-line-prefix">{prefix(row.left.type)}</span>
                      <span class="diff-line-code">{row.left.content}</span>
                    {/if}
                  </div>

                  <span class="diff-line-number diff-line-{row.right.type}">{row.right.num ?? ''}</span>
                  <div class="diff-line-content diff-line-{row.right.type}">
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
      {/each}
    {/if}
  {/if}
</div>

<style lang="scss">
  @import '../../../scss/components/item/filetab';
</style>
