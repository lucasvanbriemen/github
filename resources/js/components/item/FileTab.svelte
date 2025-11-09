<script>
  let { files = [], loadingFiles = false } = $props();
  let collapsedFiles = $state(new Set());

  // Diff view helper functions
  function toggleFile(fileName) {
    if (collapsedFiles.has(fileName)) {
      collapsedFiles.delete(fileName);
    } else {
      collapsedFiles.add(fileName);
    }
    collapsedFiles = new Set(collapsedFiles);
  }

  function getFileStatus(file) {
    if (file.from === '/dev/null') return 'added';
    if (file.to === '/dev/null') return 'deleted';
    if (file.from !== file.to) return 'renamed';
    return 'modified';
  }

  function getFileName(file) {
    return file.to === '/dev/null' ? file.from : file.to;
  }

  function processChunk(chunk) {
    const lines = [];
    let leftIndex = chunk.oldStart;
    let rightIndex = chunk.newStart;

    for (const change of chunk.changes) {
      if (change.type === 'normal') {
        lines.push({
          left: { lineNumber: leftIndex++, content: change.content, type: 'normal' },
          right: { lineNumber: rightIndex++, content: change.content, type: 'normal' }
        });
      } else if (change.type === 'del') {
        lines.push({
          left: { lineNumber: leftIndex++, content: change.content, type: 'del' },
          right: { lineNumber: null, content: '', type: 'empty' }
        });
      } else if (change.type === 'add') {
        const prevLine = lines.length > 0 ? lines[lines.length - 1] : null;
        if (prevLine && prevLine.right.type === 'empty' && prevLine.left.type === 'del') {
          lines[lines.length - 1].right = { lineNumber: rightIndex++, content: change.content, type: 'add' };
        } else {
          lines.push({
            left: { lineNumber: null, content: '', type: 'empty' },
            right: { lineNumber: rightIndex++, content: change.content, type: 'add' }
          });
        }
      }
    }

    return lines;
  }

  function getLinePrefix(type) {
    return type === 'add' ? '+' : (type === 'del' ? '-' : ' ');
  }

  // Check if file is too large to render (similar to GitHub)
  const MAX_DIFF_LINES = 400;
  function isFileTooLarge(file) {
    const totalLines = (file.additions ?? 0) + (file.deletions ?? 0);
    return totalLines > MAX_DIFF_LINES;
  }
</script>

<div class="pr-files">
  {#if loadingFiles}
    <div class="loading">Loading files...</div>
  {:else if files.length === 0}
    <div class="diff-empty">No changes</div>
  {:else}
    {#each files as file}
      {@const fileName = getFileName(file)}
      {@const fileStatus = getFileStatus(file)}
      {@const isCollapsed = collapsedFiles.has(fileName)}

      <div class="diff-file">
        <!-- File Header -->
        <button class="diff-file-header" onclick={() => toggleFile(fileName)}>
          <div class="diff-file-header-left">
            <span class="diff-file-status diff-file-status-{fileStatus}">{fileStatus}</span>
            <span class="diff-file-name">{fileName}</span>
          </div>
          <div class="diff-file-stats">
            <span class="diff-stats-additions">+{file.additions ?? 0}</span>
            <span class="diff-stats-deletions">-{file.deletions ?? 0}</span>
          </div>
        </button>

        <!-- Diff Content -->
        {#if !isCollapsed}
          {#if isFileTooLarge(file)}
            <div class="diff-too-large">
              Large diffs are not rendered.
            </div>
          {:else}
            <div class="diff-table-container">
              <table class="diff-table diff-table-side-by-side">
                <tbody>
                  {#each file.chunks as chunk}
                    {@const lines = processChunk(chunk)}

                    {#each lines as linePair}
                      <tr class="diff-line-row">
                        <!-- Left side -->
                        {#if !linePair.left || linePair.left.type === 'empty'}
                          <td class="diff-line-number diff-line-number-empty"></td>
                          <td class="diff-line-content diff-line-empty"></td>
                        {:else}
                          {@const line = linePair.left}
                          {@const typeClass = line.type === 'normal' ? '' : `diff-line-${line.type}`}
                          {@const prefix = getLinePrefix(line.type)}

                          <td class="diff-line-number {typeClass}">
                            {line.lineNumber}
                          </td>
                          <td class="diff-line-content {typeClass}">
                            <span class="diff-line-prefix">{prefix}</span>
                            <span class="diff-line-code">{line.content}</span>
                          </td>
                        {/if}

                        <!-- Right side -->
                        {#if !linePair.right || linePair.right.type === 'empty'}
                          <td class="diff-line-number diff-line-number-empty"></td>
                          <td class="diff-line-content diff-line-empty"></td>
                        {:else}
                          {@const line = linePair.right}
                          {@const typeClass = line.type === 'normal' ? '' : `diff-line-${line.type}`}
                          {@const prefix = getLinePrefix(line.type)}

                          <td class="diff-line-number {typeClass}">
                            {line.lineNumber}
                          </td>
                          <td class="diff-line-content {typeClass}">
                            <span class="diff-line-prefix">{prefix}</span>
                            <span class="diff-line-code">{line.content}</span>
                          </td>
                        {/if}
                      </tr>
                    {/each}
                  {/each}
                </tbody>
              </table>
            </div>
          {/if}
        {/if}
      </div>
    {/each}
  {/if}
</div>

<style lang="scss">
  @import '../../../scss/components/item/item.scss';
</style>
