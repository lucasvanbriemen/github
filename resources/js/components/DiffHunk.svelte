<script>
  let { diffHunk = '', path = '', startLine = null, endLine = null } = $props();

  // Parse the diff hunk and extract the relevant lines
  function parseDiffHunk(diff, start, end) {
    if (!diff) return [];

    const lines = diff.split('\n');
    const hunkLines = [];
    let currentLine = 0;

    for (const line of lines) {
      // Match hunk header: @@ -1,2 +3,4 @@
      const match = line.match(/^@@ -\d+(,\d+)? \+(\d+)(,(\d+))? @@/);
      if (match) {
        currentLine = parseInt(match[2]);
        continue;
      }

      // Determine line type
      let type = 'context';
      let displayLine = line;

      if (line.startsWith('+')) {
        type = 'added';
        // Only include lines within the range if specified
        if (start !== null && end !== null) {
          if (currentLine >= start && currentLine <= end) {
            hunkLines.push({ type, content: displayLine, lineNumber: currentLine });
          }
        } else {
          hunkLines.push({ type, content: displayLine, lineNumber: currentLine });
        }
        currentLine++;
      } else if (line.startsWith('-')) {
        type = 'removed';
        hunkLines.push({ type, content: displayLine, lineNumber: null });
        // Removed lines don't increment currentLine
      } else if (line.trim() !== '') {
        // Context line
        if (start !== null && end !== null) {
          // Include some context around the target lines
          if (currentLine >= start - 3 && currentLine <= end + 3) {
            hunkLines.push({ type, content: displayLine, lineNumber: currentLine });
          }
        } else {
          hunkLines.push({ type, content: displayLine, lineNumber: currentLine });
        }
        currentLine++;
      }
    }

    return hunkLines;
  }

  let parsedLines = $derived(parseDiffHunk(diffHunk, startLine, endLine));
</script>

{#if diffHunk && parsedLines.length > 0}
  <div class="diff-hunk">
    {#if path}
      <div class="file-name">{path}</div>
    {/if}
    <div class="diff-lines">
      {#each parsedLines as line}
        <div class="diff-line diff-line-{line.type}">
          <span class="line-number">{line.lineNumber ?? ''}</span>
          <span class="line-content">{line.content}</span>
        </div>
      {/each}
    </div>
  </div>
{/if}

<style>
  .diff-hunk {
    background-color: var(--background-color-two);
    border-radius: 1rem;
    margin-bottom: 0.5rem;
    overflow: hidden;
  }

  .file-name {
    display: block;
    padding: 0.5rem;
    width: 100%;
    background-color: var(--background-color-one);
    font-family: monospace;
    font-size: 0.875rem;
    border-bottom: 1px solid var(--border-color);
  }

  .diff-lines {
    padding: 0.25rem 0;
  }

  .diff-line {
    display: flex;
    padding: 0.1rem 0;
    font-family: monospace;
    font-size: 0.875rem;
    line-height: 1.5;
  }

  .diff-line-added {
    background-color: rgba(46, 160, 67, 0.15);
  }

  .diff-line-added .line-content {
    color: #3fb950;
  }

  .diff-line-removed {
    background-color: rgba(248, 81, 73, 0.15);
  }

  .diff-line-removed .line-content {
    color: #f85149;
  }

  .diff-line-context {
    color: var(--text-color-secondary);
  }

  .line-number {
    display: inline-block;
    width: 3rem;
    text-align: right;
    padding-right: 1rem;
    padding-left: 0.5rem;
    color: var(--text-color-secondary);
    opacity: 0.5;
    user-select: none;
    flex-shrink: 0;
  }

  .line-content {
    white-space: pre;
    overflow-x: auto;
    flex: 1;
    padding-right: 0.5rem;
  }
</style>
