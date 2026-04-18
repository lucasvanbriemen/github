<script>
  import { getContext } from 'svelte';
  import { highlightCodeInline, buildLineHtmlFromTokens } from '../utils/syntaxHighlighter.js';

  let { code = '', language = 'text', segments = null, lineType = 'normal', showWhitespace = true, lineNumber = null, side = null, searchingTerm = $bindable({ term: null }) } = $props();

  let highlightedHtml = $state('');
  let currentTheme = $state('github-light');

  const getPrecomputedTokens = getContext('precomputedTokens') ?? null;

  function detectTheme() {
    const theme = document.documentElement.getAttribute('data-theme');
    return theme === 'dark' ? 'github-dark' : 'github-light';
  }

  async function highlight(term) {
    currentTheme = detectTheme();
    let built = '';

    if (getPrecomputedTokens && lineNumber != null && side) {
      const tokenData = getPrecomputedTokens();
      const sideKey = side.toLowerCase();
      const tokens = tokenData[sideKey]?.get(lineNumber);
      if (tokens) {
        built = buildLineHtmlFromTokens(tokens, segments, lineType, showWhitespace);
      }
    }

    if (!built) {
      if (segments && Array.isArray(segments) && segments.length > 0) {
        built = await renderSegments(segments);
      } else {
        built = await highlightCodeInline(code, language, currentTheme);
      }
    }

    highlightedHtml = term ? applyMarksToHtmlString(built, term) : built;
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

  // Wrap occurrences of `term` in the rendered HTML by parsing into a detached
  // element, walking text nodes, splitting across token-span boundaries, and
  // serializing back. Runs inline with highlight() so there is no separate
  // reactive effect or DOM-binding timing concern.
  function applyMarksToHtmlString(html, term) {
    if (!html || !term) return html;
    const container = document.createElement('span');
    container.innerHTML = html;

    const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT);
    const entries = [];
    let combined = '';
    let n;
    while ((n = walker.nextNode())) {
      if (!n.nodeValue) continue;
      entries.push({ node: n, start: combined.length, end: combined.length + n.nodeValue.length });
      combined += n.nodeValue;
    }
    if (entries.length === 0) return html;

    const lowerCombined = combined.toLowerCase();
    const lowerTerm = term.toLowerCase();
    const matches = [];
    let idx = 0;
    while ((idx = lowerCombined.indexOf(lowerTerm, idx)) !== -1) {
      matches.push({ start: idx, end: idx + term.length });
      idx += term.length || 1;
    }
    if (matches.length === 0) return html;

    for (let i = matches.length - 1; i >= 0; i--) {
      wrapRange(entries, matches[i].start, matches[i].end);
    }

    return container.innerHTML;
  }

  function wrapRange(entries, start, end) {
    for (const entry of entries) {
      if (entry.end <= start) continue;
      if (entry.start >= end) break;

      const localStart = Math.max(0, start - entry.start);
      const localEnd = Math.min(entry.node.nodeValue.length, end - entry.start);
      if (localEnd <= localStart) continue;

      let target = entry.node;
      if (localStart > 0) target = target.splitText(localStart);
      if (localEnd - localStart < target.nodeValue.length) target.splitText(localEnd - localStart);

      const mark = document.createElement('mark');
      mark.className = 'search-match';
      target.parentNode.insertBefore(mark, target);
      mark.appendChild(target);
    }
  }

  $effect(() => {
    void showWhitespace;
    if (getPrecomputedTokens) void getPrecomputedTokens();
    const term = searchingTerm.term;
    highlight(term);
  });
</script>

<span class="code">{@html highlightedHtml}</span>
