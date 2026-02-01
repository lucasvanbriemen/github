<script>
  import { onMount } from 'svelte';
  import { highlightCodeInline } from '../utils/syntaxHighlighter.js';

  let { code = '', language = 'text', segments = null, lineType = 'normal', showWhitespace = true } = $props();

  let highlightedHtml = $state('');
  let currentTheme = $state('github-light');

  // Detect theme from document attribute
  function detectTheme() {
    const theme = document.documentElement.getAttribute('data-theme');
    return theme === 'dark' ? 'github-dark' : 'github-light';
  }

  async function highlight() {
    currentTheme = detectTheme();

    // If segments exist (intra-line diff), render them with highlighting
    if (segments && Array.isArray(segments) && segments.length > 0) {
      highlightedHtml = await renderSegments(segments);
    } else {
      // Fall back to full-line syntax highlighting
      highlightedHtml = await highlightCodeInline(code, language, currentTheme);
    }
  }

  async function renderSegments(segs) {
    let html = '';

    for (const seg of segs) {
      const highlighted = await highlightCodeInline(seg.text, language, currentTheme);

      if (seg.type === 'change') {
        // Check if this change segment is whitespace-only
        const isWhitespaceOnly = /^\s*$/.test(seg.text);

        // Skip rendering whitespace-only highlights if hiding whitespace
        if (isWhitespaceOnly && !showWhitespace) {
          html += highlighted;
          continue;
        }

        // Wrap changed segments in a span with special highlighting
        const isAdd = lineType === 'add';
        const isDel = lineType === 'del';
        const bgColor = isAdd ? 'rgba(34, 197, 94, 0.25)' : isDel ? 'rgba(239, 68, 68, 0.25)' : 'rgba(234, 179, 8, 0.25)';
        html += `<span class="segment-change" style="background-color: ${bgColor}; border-radius: 0.25rem;">${highlighted}</span>`;
      } else {
        // Equal segments render normally
        html += highlighted;
      }
    }

    return html;
  }

  onMount(() => {
    highlight();
  });

  // Re-highlight when showWhitespace changes
  $effect(() => {
    void showWhitespace;
    highlight();
  });
</script>

<span class="code">{@html highlightedHtml}</span>
