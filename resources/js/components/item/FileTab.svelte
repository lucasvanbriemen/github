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
              {#each (hunk.rows || []) as changedLinePair}
                <div class="diff-row">
                  <span class="diff-line-number diff-line-{changedLinePair.left.type}">{changedLinePair.left.num}</span>
                  <div class="diff-line-content diff-line-{changedLinePair.left.type}">
                    {#if changedLinePair.left.type !== 'empty'}
                      <span class="diff-line-prefix">{prefix(changedLinePair.left.type)}</span>
                      <span class="diff-line-code">{changedLinePair.left.content}</span>
                    {/if}
                  </div>

                  <span class="diff-line-number diff-line-{changedLinePair.right.type}">{changedLinePair.right.num}</span>
                  <div class="diff-line-content diff-line-{changedLinePair.right.type}">
                    {#if changedLinePair.right.type !== 'empty'}
                      <span class="diff-line-prefix">{prefix(changedLinePair.right.type)}</span>
                      <span class="diff-line-code">{changedLinePair.right.content}</span>
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
