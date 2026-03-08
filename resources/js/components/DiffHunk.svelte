<script>
  let { diffHunk = '', path = '', startLine = null, endLine = null } = $props();

  // Parse the diff hunk and extract the relevant lines
  // Matches GitHub's behavior: minimal context (1 line before/after) around the comment range
  function parseDiffHunk(diff, start, end) {
    if (!diff) return [];

    const lines = diff.split('\n');
    const hunkLines = [];
    let currentLine = 0;
    const contextLines = 1; // GitHub shows only 1 line of context before/after

    for (const line of lines) {
      // Match hunk header: @@ -1,2 +3,4 @@
      const match = line.match(/^@@ -\d+(,\d+)? \+(\d+)(,(\d+))? @@/);
      if (match) {
        currentLine = parseInt(match[2]);
        continue;
      }

      let type = 'context';
      const codeContent = line.slice(1); // Strip the diff marker (first character)
      const shouldShowLine = end === null; // Show all lines if not a comment-specific diff

      if (line.startsWith('+')) {
        type = 'added';
        if (shouldShowLine || (currentLine >= (start ?? end) - contextLines && currentLine <= end + contextLines)) {
          hunkLines.push({ type, content: codeContent, lineNumber: currentLine });
        }
        currentLine++;
      } else if (line.startsWith('-')) {
        type = 'removed';
        // Only show removed lines for full diffs, not for comment-specific diffs
        if (shouldShowLine) {
          hunkLines.push({ type, content: codeContent, lineNumber: null });
        }
      } else if (line.trim() !== '') {
        type = 'context';
        if (shouldShowLine || (currentLine >= (start ?? end) - contextLines && currentLine <= end + contextLines)) {
          hunkLines.push({ type, content: codeContent, lineNumber: currentLine });
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
