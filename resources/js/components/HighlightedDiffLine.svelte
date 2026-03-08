<script>
  import { getContext } from 'svelte';
  import { highlightCodeInline, buildLineHtmlFromTokens } from '../utils/syntaxHighlighter.js';

  let { code = '', language = 'text', segments = null, lineType = 'normal', showWhitespace = true, lineNumber = null, side = null } = $props();

  let highlightedHtml = $state('');
  let currentTheme = $state('github-light');

  const getPrecomputedTokens = getContext('precomputedTokens') ?? null;

  // Detect theme from document attribute
  function detectTheme() {
    const theme = document.documentElement.getAttribute('data-theme');
    return theme === 'dark' ? 'github-dark' : 'github-light';
  }

  async function highlight() {
    currentTheme = detectTheme();

    // Try pre-computed tokens first (correct cross-line grammar state)
    if (getPrecomputedTokens && lineNumber != null && side) {
      const tokenData = getPrecomputedTokens();
      const sideKey = side.toLowerCase();
      const tokens = tokenData[sideKey]?.get(lineNumber);

      if (tokens) {
        highlightedHtml = buildLineHtmlFromTokens(tokens, segments, lineType, showWhitespace);
        return;
      }
    }

    // Fallback: individual per-segment highlighting
    if (segments && Array.isArray(segments) && segments.length > 0) {
      highlightedHtml = await renderSegments(segments);
    } else {
      highlightedHtml = await highlightCodeInline(code, language, currentTheme);
    }
  }

  async function renderSegments(segs) {
    let html = '';

    for (const seg of segs) {
      const highlighted = await highlightCodeInline(seg.text, language, currentTheme);

      if (seg.type === 'change') {
        const isWhitespaceOnly = /^\s*$/.test(seg.text);

        if (isWhitespaceOnly && !showWhitespace) {
          html += highlighted;
          continue;
        }

        const isAdd = lineType === 'add';
        const isDel = lineType === 'del';
        const bgColor = isAdd ? 'rgba(34, 197, 94, 0.25)' : isDel ? 'rgba(239, 68, 68, 0.25)' : 'rgba(234, 179, 8, 0.25)';
        html += `<span class="segment-change" style="background-color: ${bgColor}; border-radius: 0.25rem;">${highlighted}</span>`;
      } else {
        html += highlighted;
      }
    }

    return html;
  }

  // Re-highlight when showWhitespace changes or pre-computed tokens arrive
  $effect(() => {
    void showWhitespace;
    if (getPrecomputedTokens) {
      void getPrecomputedTokens();
    }
    highlight();
  });
</script>

<span class="code">{@html highlightedHtml}</span>
