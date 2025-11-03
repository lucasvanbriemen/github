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
        if (end !== null) {
          const effectiveStart = start ?? end - 3; // Default to 3 lines before end if start not provided
          if (currentLine >= effectiveStart && currentLine <= end) {
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
        if (end !== null) {
          const effectiveStart = start ?? end - 3; // Default to 3 lines before end if start not provided
          // Include some context around the target lines
          if (currentLine >= effectiveStart - 3 && currentLine <= end + 3) {
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
  @import '../../scss/components/diff-hunk.scss';
</style>
